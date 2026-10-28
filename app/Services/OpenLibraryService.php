<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenLibraryService
{
    public function byIsbn(string $isbn): array
    {
        try {
            $response = Http::timeout(5)->get('https://openlibrary.org/api/books', [
                'bibkeys' => "ISBN:{$isbn}",
                'format' => 'json',
                'jscmd' => 'data',
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Open Library no respondió: '.$e->getMessage());

            return [];
        }

        if ($response->failed()) {
            return [];
        }

        $data = $response->json("ISBN:{$isbn}");

        if (! $data) {
            return [];
        }

        return [[
            'title' => $data['title'] ?? 'Sin título',
            'language' => null,
            'author' => collect($data['authors'] ?? [])->pluck('name')->implode(', '),
            'isbn' => $isbn,
            'publisher' => $data['publishers'][0]['name'] ?? null,
            'published_year' => isset($data['publish_date'])
                ? (int) substr($data['publish_date'], -4)
                : null,
            'cover_url' => $data['cover']['large'] ?? $data['cover']['medium'] ?? null,
        ]];
    }

    public function search(string $query): array
    {
        try {
            $response = Http::timeout(5)->get('https://openlibrary.org/search.json', [
                'q' => $query,
                'limit' => 10,
                'fields' => 'title,author_name,isbn,publisher,first_publish_year,cover_i,cover_edition_key,edition_key,language',
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Open Library no respondió: '.$e->getMessage());

            return [];
        }

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('docs', []))
            ->map(fn (array $doc) => [
                'title' => $doc['title'] ?? 'Sin título',
                'language' => isset($doc['language'][0])
                    ? strtolower(substr($doc['language'][0], 0, 2))
                    : null,
                'author' => implode(', ', $doc['author_name'] ?? []),
                'isbn' => $doc['isbn'][0] ?? null,
                'publisher' => $doc['publisher'][0] ?? null,
                'published_year' => $doc['first_publish_year'] ?? null,
                'cover_url' => $this->coverUrl($doc),
            ])
            ->values()
            ->all();
    }

    private function coverUrl(array $doc): ?string
    {
        if (isset($doc['cover_i'])) {
            return "https://covers.openlibrary.org/b/id/{$doc['cover_i']}-M.jpg";
        }

        $olid = $doc['cover_edition_key'] ?? $doc['edition_key'][0] ?? null;

        return $olid
            ? "https://covers.openlibrary.org/b/olid/{$olid}-M.jpg"
            : null;
    }
}
