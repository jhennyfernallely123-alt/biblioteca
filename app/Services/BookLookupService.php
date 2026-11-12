<?php

namespace App\Services;

use App\Models\CatalogBook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BookLookupService
{
    public function __construct(
        private GoogleBooksService $google,
        private OpenLibraryService $openLibrary,
        private TranslationService $translator,
    ) {}

    public function byIsbn(string $value): array
    {
        $isbn = Isbn::normalize($value);

        if (! Isbn::isValid($isbn)) {
            throw ValidationException::withMessages([
                'isbn' => 'El ISBN no es válido.',
            ]);
        }

        $local = $this->localByIsbn($isbn);

        $results = Cache::remember(
            "lookup:v5:isbn:{$isbn}",
            now()->addDay(),
            fn () => $this->google->byIsbn($isbn) ?: $this->openLibrary->byIsbn($isbn)
        );

        return [
            ...$local,
            ...$this->translator->translateResults($results, app()->getLocale()),
        ];
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return [];
        }

        $local = $this->localSearch($query);

        $results = Cache::remember(
            'lookup:v6:q:'.md5($query),
            now()->addDay(),
            fn () => $this->google->search($query) ?: $this->openLibrary->search($query)
        );

        $locale = app()->getLocale();

        return [
            ...$local,
            ...$this->translator->translateResults(
                $this->withOfficialTitles($results, $locale),
                $locale
            ),
        ];
    }

    private function localByIsbn(string $isbn): array
    {
        return CatalogBook::query()
            ->where('isbn', $isbn)
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn (CatalogBook $book) => $this->formatLocal($book))
            ->all();
    }

    private function localSearch(string $query): array
    {
        $term = '%'.$this->escapeLike($query).'%';

        $builder = CatalogBook::query()
            ->where(function ($builder) use ($term) {
                $builder->where('title', 'like', $term)
                    ->orWhere('author', 'like', $term);
            });

        if (Isbn::isValid($query)) {
            $builder->orWhere('isbn', Isbn::normalize($query));
        }

        return $builder
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn (CatalogBook $book) => $this->formatLocal($book))
            ->all();
    }

    private function formatLocal(CatalogBook $book): array
    {
        return [
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'publisher' => $book->publisher,
            'published_year' => $book->published_year,
            'cover_url' => $book->cover_path
                ? route('catalog-covers.show', $book, false)
                : null,
            'catalog_book_id' => $book->id,
        ];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    private function withOfficialTitles(array $results, string $locale): array
    {
        if ($locale !== 'es') {
            return $results;
        }

        $candidates = [];

        foreach ($results as $index => $result) {
            if (($result['work_key'] ?? null) === null) {
                continue;
            }

            $candidates[$index] = $result;
        }

        foreach ($this->openLibrary->spanishTitles($candidates) as $index => $title) {
            $current = $results[$index]['title'];

            if (mb_strtolower($title) !== mb_strtolower($current)) {
                $results[$index]['original_title'] = $current;
            }

            $results[$index]['title'] = $title;
            $results[$index]['language'] = 'es';
        }

        return $results;
    }
}
