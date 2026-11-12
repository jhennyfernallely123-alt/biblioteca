<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    private const ISO_ALIASES = [
        'sp' => 'es',
        'iw' => 'he',
        'ji' => 'yi',
        'in' => 'id',
        'jw' => 'jv',
        'mo' => 'ro',
        'sh' => 'sr',
        'fil' => 'tl',
        'tgl' => 'tl',
    ];

    private const ISO3_TO_ISO1 = [
        'afr' => 'af', 'alb' => 'sq', 'amh' => 'am', 'ara' => 'ar', 'arm' => 'hy',
        'aze' => 'az', 'baq' => 'eu', 'ben' => 'bn', 'bos' => 'bs', 'bul' => 'bg',
        'bur' => 'my', 'cat' => 'ca', 'chi' => 'zh', 'cze' => 'cs', 'dan' => 'da',
        'dut' => 'nl', 'eng' => 'en', 'epo' => 'eo', 'est' => 'et', 'fin' => 'fi',
        'fre' => 'fr', 'geo' => 'ka', 'ger' => 'de', 'gle' => 'ga', 'glg' => 'gl',
        'gre' => 'el', 'guj' => 'gu', 'heb' => 'he', 'hin' => 'hi', 'hrv' => 'hr',
        'hun' => 'hu', 'ice' => 'is', 'ind' => 'id', 'ita' => 'it', 'jav' => 'jv',
        'jpn' => 'ja', 'kan' => 'kn', 'kaz' => 'kk', 'khm' => 'km', 'kor' => 'ko',
        'lao' => 'lo', 'lat' => 'la', 'lav' => 'lv', 'lit' => 'lt', 'mac' => 'mk',
        'mal' => 'ml', 'mar' => 'mr', 'may' => 'ms', 'mon' => 'mn', 'nep' => 'ne',
        'nor' => 'no', 'nob' => 'no', 'nno' => 'nn', 'pan' => 'pa', 'per' => 'fa',
        'pol' => 'pl', 'por' => 'pt', 'rum' => 'ro', 'rus' => 'ru', 'sin' => 'si',
        'slo' => 'sk', 'slv' => 'sl', 'spa' => 'es', 'srp' => 'sr', 'swa' => 'sw',
        'swe' => 'sv', 'tam' => 'ta', 'tel' => 'te', 'tha' => 'th', 'tib' => 'bo',
        'tur' => 'tr', 'ukr' => 'uk', 'urd' => 'ur', 'uzb' => 'uz', 'vie' => 'vi',
        'wel' => 'cy', 'xho' => 'xh', 'yid' => 'yi', 'zul' => 'zu',
        'ces' => 'cs', 'deu' => 'de', 'ell' => 'el', 'eus' => 'eu', 'fas' => 'fa',
        'fra' => 'fr', 'hye' => 'hy', 'isl' => 'is', 'kat' => 'ka', 'mkd' => 'mk',
        'mya' => 'my', 'nld' => 'nl', 'ron' => 'ro', 'slk' => 'sk', 'sqi' => 'sq',
        'zho' => 'zh',
    ];

    public function translateText(string $text, string $targetLocale, ?string $sourceLanguage = null): ?string
    {
        if (trim($text) === '' || $this->isSameLanguage($sourceLanguage, $targetLocale)) {
            return null;
        }

        $key = $this->cacheKey($targetLocale, $text);
        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            if ($this->isTranslationError($cached)) {
                Cache::forget($key);
            } else {
                return $cached;
            }
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
                if ($this->isTranslationError($cached)) {
                    Cache::forget($this->cacheKey($targetLocale, $title));
                } else {
                    $results[$index]['original_title'] = $title;
                    $results[$index]['title'] = $cached;

                    continue;
                }
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
                            $source,
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

            if (! is_string($translated) || trim($translated) === '') {
                continue;
            }

            if ($this->isTranslationError($translated, $response->json('responseStatus'))) {
                continue;
            }

            $translations[$index] = trim(html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return $translations;
    }

    private function params(string $text, ?string $source, string $target): array
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

    private function apiCode(?string $locale): string
    {
        $code = strtolower(trim((string) $locale));

        if ($code === '') {
            return 'Autodetect';
        }

        $mapped = strtolower((string) config("languages.supported.{$code}", $code));

        if (isset(self::ISO_ALIASES[$mapped])) {
            return self::ISO_ALIASES[$mapped];
        }

        if (isset(self::ISO3_TO_ISO1[$mapped])) {
            return self::ISO3_TO_ISO1[$mapped];
        }

        if (preg_match('/^([a-z]{2})(?:[_-][a-z0-9]+)?$/', $mapped, $matches)) {
            return $matches[1];
        }

        return 'Autodetect';
    }

    private function isSameLanguage(?string $source, string $target): bool
    {
        if (! $source) {
            return false;
        }

        $sourceCode = $this->apiCode($source);

        return $sourceCode !== 'Autodetect' && $sourceCode === $this->apiCode($target);
    }

    private function isTranslationError(string $text, mixed $status = null): bool
    {
        if ($status !== null && (string) $status !== '200') {
            return true;
        }

        $upper = strtoupper($text);

        foreach ([
            'MYMEMORY WARNING',
            'INVALID SOURCE LANGUAGE',
            'INVALID TARGET LANGUAGE',
            'PLEASE SELECT TWO DISTINCT LANGUAGES',
            'QUERY LENGTH LIMIT',
        ] as $marker) {
            if (str_contains($upper, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function cacheKey(string $locale, string $text): string
    {
        return 'translation:'.$locale.':'.md5($text);
    }
}
