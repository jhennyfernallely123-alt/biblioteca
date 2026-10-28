<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    public function translateText(string $text, string $targetLocale, ?string $sourceLanguage = null): ?string
    {
        if (trim($text) === '' || $this->isSameLanguage($sourceLanguage, $targetLocale)) {
            return null;
        }

        $key = $this->cacheKey($targetLocale, $text);
        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $translated = $this->fetch([[$text, $sourceLanguage]], $targetLocale)[0] ?? null;

        if ($translated !== null) {
            Cache::put($key, $translated, now()->addDays(30));
        }

        return $translated;
    }

    public function translateResults(array $results, string $targetLocale): array
    {
        $positions = [];
        $texts = [];

        foreach ($results as $index => $result) {
            $title = $result['title'] ?? null;
            $source = $result['language'] ?? null;

            if (! $title || $this->isSameLanguage($source, $targetLocale)) {
                continue;
            }

            $cached = Cache::get($this->cacheKey($targetLocale, $title));

            if (is_string($cached) && $cached !== '') {
                $results[$index]['original_title'] = $title;
                $results[$index]['title'] = $cached;

                continue;
            }

            $positions[] = $index;
            $texts[] = [$title, $source];
        }

        $translations = $this->fetch($texts, $targetLocale);

        foreach ($positions as $position => $index) {
            $translated = $translations[$position] ?? null;

            if ($translated === null) {
                continue;
            }

            $title = $results[$index]['title'];
            Cache::put($this->cacheKey($targetLocale, $title), $translated, now()->addDays(30));
            $results[$index]['original_title'] = $title;
            $results[$index]['title'] = $translated;
        }

        return $results;
    }

    private function fetch(array $items, string $targetLocale): array
    {
        if ($items === []) {
            return [];
        }

        $target = $this->apiCode($targetLocale);

        try {
            $responses = Http::pool(function (Pool $pool) use ($items, $target) {
                foreach ($items as $index => [$text, $source]) {
                    $pool->as('t'.$index)
                        ->timeout(5)
                        ->get('https://api.mymemory.translated.net/get', $this->params(
                            $text,
                            $source ?: 'en',
                            $target
                        ));
                }
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudieron traducir los textos: '.$e->getMessage());

            return [];
        }

        $translations = [];

        foreach ($items as $index => [$text]) {
            $response = $responses['t'.$index] ?? null;

            if (! $response instanceof Response || ! $response->ok()) {
                continue;
            }

            $translated = $response->json('responseData.translatedText');

            if (! is_string($translated)
                || trim($translated) === ''
                || str_contains(strtolower($translated), 'mymemory warning')) {
                continue;
            }

            $translations[$index] = trim(html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return $translations;
    }

    private function params(string $text, string $source, string $target): array
    {
        $params = [
            'q' => $text,
            'langpair' => $this->apiCode($source).'|'.$target,
        ];

        if ($email = config('services.mymemory.email')) {
            $params['de'] = $email;
        }

        return $params;
    }

    private function apiCode(string $locale): string
    {
        return config("languages.supported.{$locale}", $locale);
    }

    private function isSameLanguage(?string $source, string $target): bool
    {
        if (! $source) {
            return false;
        }

        return substr(strtolower($source), 0, 2) === substr(strtolower($target), 0, 2);
    }

    private function cacheKey(string $locale, string $text): string
    {
        return 'translation:'.$locale.':'.md5($text);
    }
}
