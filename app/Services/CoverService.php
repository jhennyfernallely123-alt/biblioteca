<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CoverService
{
    public function storeUploaded(UploadedFile $file): string
    {
        return $file->store('covers', 'public');
    }

    public function storeFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            $url = preg_replace('/^http:\/\//i', 'https://', $url);

            $response = Http::timeout(10)->get($url);

            if ($response->failed()) {
                return null;
            }

            $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (! isset($extensions[$mime]) || strlen($response->body()) > 5 * 1024 * 1024) {
                return null;
            }

            $path = 'covers/'.Str::uuid().'.'.$extensions[$mime];
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
