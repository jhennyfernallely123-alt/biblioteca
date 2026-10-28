<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleBooksService
{
    public function byIsbn(string $isbn): array
    {
        return $this->fetch(['q' => "isbn:{$isbn}"]);
    }

    public function search(string $query): array
    {
        return $this->fetch(['q' => $query]);
    }

    private function fetch(array $params): array
    {
        $params = array_filter($params + [
            'key' => config('services.google_books.key'),
        ]);

        try {
            $response = Http::timeout(5)->get(
                'https://www.googleapis.com/books/v1/volumes',
                $params
            );
        } catch (ConnectionException $e) {
            Log::warning('Google Books no respondió: '.$e->getMessage());

            return [];
        }

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('items', []))
            ->map(fn (array $item) => $this->map($item))
            ->filter()
            ->values()
            ->all();
    }

    private function map(array $item): ?array
    {
        $info = $item['volumeInfo'] ?? [];
        $title = $info['title'] ?? null;

        if (! $title) {
            return null;
        }

        $identifiers = collect($info['industryIdentifiers'] ?? []);
        $isbn13 = $identifiers->firstWhere('type', 'ISBN_13');
        $isbn10 = $identifiers->firstWhere('type', 'ISBN_10');

        return [
            'title' => $title,
            'language' => isset($info['language']) ? strtolower(substr($info['language'], 0, 2)) : null,
            'author' => implode(', ', $info['authors'] ?? []),
            'isbn' => $isbn13['identifier'] ?? $isbn10['identifier'] ?? null,
            'publisher' => $info['publisher'] ?? null,
            'published_year' => isset($info['publishedDate'])
                ? (int) substr($info['publishedDate'], 0, 4)
                : null,
            'cover_url' => isset($info['imageLinks']['thumbnail'])
                ? str_replace('http://', 'https://', $info['imageLinks']['thumbnail'])
                : null,
        ];
    }
}
