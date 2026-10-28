<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class CatalogService
{
    public function source(): array
    {
        return array_merge(
            $this->json(lang_path('es.json')),
            $this->flatten(require lang_path('es/validation.php'), 'validation'),
            $this->flatten(require lang_path('es/auth.php'), 'auth'),
            $this->flatten(require lang_path('es/passwords.php'), 'passwords'),
        );
    }

    public function keys(): array
    {
        return array_keys($this->source());
    }

    public function all(string $locale): array
    {
        if ($locale === 'es') {
            return $this->source();
        }

        return array_merge(
            $this->json(lang_path("{$locale}.json")),
            $this->json($this->stagingPath($locale)),
            $this->json($this->storagePath($locale)),
        );
    }

    public function ui(string $locale): array
    {
        return array_filter(
            $this->all($locale),
            fn (string $key) => str_starts_with($key, 'ui.'),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function missing(string $locale): array
    {
        return array_values(array_diff($this->keys(), array_keys($this->all($locale))));
    }

    public function isComplete(string $locale): bool
    {
        return $this->missing($locale) === [];
    }

    public function writeGenerated(string $locale, array $lines): void
    {
        $path = $this->storagePath($locale);
        File::ensureDirectoryExists(dirname($path));
        $tmp = $path.'.tmp';
        File::put($tmp, json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::move($tmp, $path);
    }

    public function storagePath(string $locale): string
    {
        return storage_path("app/i18n/{$locale}.json");
    }

    public function stagingPath(string $locale): string
    {
        return storage_path("app/i18n/pending/{$locale}.json");
    }

    private function json(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : [];
    }

    private function flatten(array $lines, string $prefix): array
    {
        $flat = [];

        foreach ($lines as $key => $value) {
            $full = "{$prefix}.{$key}";

            if (is_array($value)) {
                $flat += $this->flatten($value, $full);
            } else {
                $flat[$full] = $value;
            }
        }

        return $flat;
    }
}
