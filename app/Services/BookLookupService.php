<?php

namespace App\Services;

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

        $results = Cache::remember(
            "lookup:v4:isbn:{$isbn}",
            now()->addDay(),
            fn () => $this->google->byIsbn($isbn) ?: $this->openLibrary->byIsbn($isbn)
        );

        return $this->translator->translateResults($results, app()->getLocale());
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return [];
        }

        $results = Cache::remember(
            'lookup:v4:q:'.md5($query),
            now()->addDay(),
            fn () => $this->google->search($query) ?: $this->openLibrary->search($query)
        );

        return $this->translator->translateResults($results, app()->getLocale());
    }
}
