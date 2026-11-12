<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
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
                'fields' => 'key,title,author_name,isbn,publisher,first_publish_year,cover_i,cover_edition_key,edition_key,language',
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
                    ? strtolower(trim($doc['language'][0]))
                    : null,
                'author' => implode(', ', $doc['author_name'] ?? []),
                'isbn' => $doc['isbn'][0] ?? null,
                'publisher' => $doc['publisher'][0] ?? null,
                'published_year' => $doc['first_publish_year'] ?? null,
                'cover_url' => $this->coverUrl($doc),
                'work_key' => $doc['key'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{work_key?: ?string, title?: ?string}>  $books
     * @return array<int, string>
     */
    public function spanishTitles(array $books): array
    {
        $titles = [];
        $pending = [];

        foreach ($books as $index => $book) {
            $workKey = $book['work_key'] ?? null;

            if (! is_string($workKey) || ! str_starts_with($workKey, '/works/')) {
                continue;
            }

            $cacheKey = 'openlibrary:spanish:'.md5($workKey);
            $cached = Cache::get($cacheKey);

            if (is_string($cached)) {
                if ($cached !== '') {
                    $titles[$index] = $cached;
                }

                continue;
            }

            $pending[$index] = $workKey;
        }

        if ($pending === []) {
            return $titles;
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($pending) {
                foreach ($pending as $index => $workKey) {
                    $pool->as('e'.$index)
                        ->timeout(5)
                        ->get('https://openlibrary.org'.$workKey.'/editions.json', ['limit' => 100]);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudieron consultar las ediciones de Open Library: '.$e->getMessage());

            return $titles;
        }

        foreach ($pending as $index => $workKey) {
            $response = $responses['e'.$index] ?? null;

            if (! $response instanceof Response || ! $response->ok()) {
                continue;
            }

            $title = $this->pickSpanishTitle($response->json('entries', []));

            Cache::put('openlibrary:spanish:'.md5($workKey), $title ?? '', now()->addDay());

            if ($title !== null) {
                $titles[$index] = $title;
            }
        }

        return $titles;
    }

    private function pickSpanishTitle(array $entries): ?string
    {
        $candidates = collect($entries)
            ->filter(fn ($edition) => is_array($edition) && ! empty($edition['title']))
            ->filter(fn (array $edition) => collect($edition['languages'] ?? [])
                ->contains(fn ($language) => ($language['key'] ?? null) === '/languages/spa'))
            ->map(fn (array $edition) => [
                'title' => trim((string) $edition['title']),
                'date' => (string) ($edition['publish_date'] ?? ''),
            ])
            ->filter(fn (array $edition) => $edition['title'] !== '');

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->groupBy(fn (array $edition) => mb_strtolower($edition['title']))
            ->map(fn ($group) => [
                'title' => $group->first()['title'],
                'count' => $group->count(),
                'date' => $group->max('date'),
            ])
            ->sortBy([
                ['count', 'desc'],
                ['date', 'desc'],
            ])
            ->first()['title'];
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
