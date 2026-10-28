<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogTranslationService
{
    public function __construct(private CatalogService $catalog) {}

    public function translateKeys(string $locale, array $keys): array
    {
        $source = $this->catalog->source();
        $staging = $this->staging($locale);
        $pending = [];

        foreach ($keys as $key) {
            if (! isset($source[$key]) || isset($staging[$key])) {
                continue;
            }

            $cached = Cache::get($this->cacheKey($locale, $source[$key]));

            if (is_string($cached) && $cached !== '') {
                $staging[$key] = $cached;

                continue;
            }

            $pending[$key] = $source[$key];
        }

        if ($pending !== []) {
            $translated = $this->fetch($pending, $locale);

            if (count($translated) !== count($pending)) {
                $this->saveStaging($locale, $staging);

                return ['remaining' => count($this->catalog->missing($locale)), 'failed' => count($pending) - count($translated)];
            }

            foreach ($translated as $key => $value) {
                $staging[$key] = $value;
                Cache::put($this->cacheKey($locale, $source[$key]), $value, now()->addDays(30));
            }
        }

        $this->saveStaging($locale, $staging);

        return ['remaining' => count($this->catalog->missing($locale)), 'failed' => 0];
    }

    public function nextKeys(string $locale, int $limit = 20): array
    {
        return array_slice($this->catalog->missing($locale), 0, $limit);
    }

    public function finalize(string $locale): bool
    {
        $staging = $this->staging($locale);

        if (count(array_diff($this->catalog->keys(), array_keys($staging))) > 0) {
            return false;
        }

        $this->catalog->writeGenerated($locale, $staging);
        File::delete($this->catalog->stagingPath($locale));

        return true;
    }

    private function fetch(array $items, string $locale): array
    {
        $target = config("languages.supported.{$locale}", $locale);
        $protected = [];
        $tokens = [];

        try {
            $responses = Http::pool(function ($pool) use ($items, $target, &$protected, &$tokens) {
                foreach ($items as $key => $text) {
                    [$protected[$key], $tokens[$key]] = $this->protect($text);

                    $pool->as($key)
                        ->timeout(8)
                        ->get('https://api.mymemory.translated.net/get', $this->params($protected[$key], $target));
                }
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudo preparar el catálogo: '.$e->getMessage());

            return [];
        }

        $result = [];

        foreach ($items as $key => $text) {
            $response = $responses[$key] ?? null;

            if (! $response instanceof Response || ! $response->ok()) {
                continue;
            }

            $translated = $response->json('responseData.translatedText');

            if (! is_string($translated)
                || trim($translated) === ''
                || str_contains(strtolower($translated), 'mymemory warning')) {
                continue;
            }

            $translated = html_entity_decode(trim($translated), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            foreach ($tokens[$key] ?? [] as $token => $original) {
                $translated = str_replace($token, $original, $translated);
            }

            foreach ($tokens[$key] ?? [] as $original) {
                if (! str_contains($translated, $original)) {
                    continue 2;
                }
            }

            $result[$key] = $translated;
        }

        return $result;
    }

    private function protect(string $text): array
    {
        $tokens = [];
        $protected = preg_replace_callback('/:[a-zA-Z_][a-zA-Z0-9_]*|\|/', function ($match) use (&$tokens) {
            $token = '__T'.count($tokens).'__';
            $tokens[$token] = $match[0];

            return $token;
        }, $text);

        return [$protected, $tokens];
    }

    private function params(string $text, string $target): array
    {
        $params = [
            'q' => $text,
            'langpair' => 'es-ES|'.$target,
        ];

        if ($email = config('services.mymemory.email')) {
            $params['de'] = $email;
        }

        return $params;
    }

    private function staging(string $locale): array
    {
        $path = $this->catalog->stagingPath($locale);

        if (! is_file($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : [];
    }

    private function saveStaging(string $locale, array $lines): void
    {
        $path = $this->catalog->stagingPath($locale);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function cacheKey(string $locale, string $text): string
    {
        return 'catalog:'.$locale.':'.md5($text);
    }
}
