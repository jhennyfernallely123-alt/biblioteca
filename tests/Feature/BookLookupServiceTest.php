<?php

use App\Services\BookLookupService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

it('encuentra un libro por isbn en Google Books', function () {
    Http::fake([
        'www.googleapis.com/books/v1/volumes*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'Rayuela',
                    'authors' => ['Julio Cortázar'],
                    'publisher' => 'Alfaguara',
                    'publishedDate' => '1963-06-28',
                    'imageLinks' => ['thumbnail' => 'http://covers.test/rayuela.jpg'],
                ],
            ]],
        ]),
    ]);

    $result = app(BookLookupService::class)->byIsbn('9788437604947');

    expect($result)->not->toBeEmpty()
        ->and($result[0]['title'])->toBe('Rayuela')
        ->and($result[0]['author'])->toBe('Julio Cortázar')
        ->and($result[0]['published_year'])->toBe(1963)
        ->and($result[0]['cover_url'])->toBe('https://covers.test/rayuela.jpg');
});

it('usa Open Library cuando Google Books falla', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([], 500),
        'openlibrary.org/api/books*' => Http::response([
            'ISBN:9788437604947' => [
                'title' => 'Rayuela',
                'authors' => [['name' => 'Julio Cortázar']],
                'publishers' => [['name' => 'Alfaguara']],
                'publish_date' => '1963',
                'cover' => ['medium' => 'https://covers.test/rayuela.jpg'],
                'cover_edition_key' => 'OL123M',
            ],
        ]),
    ]);

    $result = app(BookLookupService::class)->byIsbn('9788437604947');

    expect($result[0]['title'])->toBe('Rayuela')
        ->and($result[0]['author'])->toBe('Julio Cortázar')
        ->and($result[0]['cover_url'])->toBe('https://covers.test/rayuela.jpg');
});

it('rechaza un isbn inválido', function () {
    app(BookLookupService::class)->byIsbn('123');
})->throws(ValidationException::class);

it('usa Open Library cuando Google Books no puede conectar', function () {
    Http::fake([
        'www.googleapis.com/*' => fn () => throw new ConnectionException('SSL certificate problem'),
        'openlibrary.org/api/books*' => Http::response([
            'ISBN:9788437604947' => [
                'title' => 'Rayuela',
                'authors' => [['name' => 'Julio Cortázar']],
            ],
        ]),
    ]);

    $result = app(BookLookupService::class)->byIsbn('9788437604947');

    expect($result[0]['title'])->toBe('Rayuela');
});

it('devuelve una lista vacía cuando ningún catálogo puede conectar', function () {
    Http::fake(fn () => throw new ConnectionException('SSL certificate problem'));

    $result = app(BookLookupService::class)->byIsbn('9788437604947');

    expect($result)->toBe([]);
});

it('usa la edición de Open Library como portada cuando falta cover_i', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([], 500),
        'openlibrary.org/search.json*' => Http::response([
            'docs' => [[
                'title' => 'Rayuela',
                'author_name' => ['Julio Cortázar'],
                'isbn' => ['9788437604947'],
                'cover_edition_key' => 'OL123M',
            ]],
        ]),
    ]);

    $result = app(BookLookupService::class)->search('rayuela');

    expect($result[0]['cover_url'])
        ->toBe('https://covers.openlibrary.org/b/olid/OL123M-M.jpg');
});

it('traduce al español los títulos de libros en inglés', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The Hobbit',
                    'authors' => ['J. R. R. Tolkien'],
                    'language' => 'en',
                ],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'El Hobbit'],
            'responseStatus' => 200,
        ]),
    ]);

    $result = app(BookLookupService::class)->search('the hobbit');

    expect($result[0]['title'])->toBe('El Hobbit')
        ->and($result[0]['original_title'])->toBe('The Hobbit')
        ->and($result[0]['author'])->toBe('J. R. R. Tolkien');
});

it('mantiene el título original si la traducción falla', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The Hobbit',
                    'language' => 'en',
                ],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([], 500),
    ]);

    $result = app(BookLookupService::class)->search('the hobbit');

    expect($result[0]['title'])->toBe('The Hobbit')
        ->and($result[0])->not->toHaveKey('original_title');
});

it('no traduce los títulos que ya están en español', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'Rayuela',
                    'language' => 'es',
                ],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'Rayuela'],
        ]),
    ]);

    $result = app(BookLookupService::class)->search('rayuela');

    expect($result[0]['title'])->toBe('Rayuela')
        ->and($result[0])->not->toHaveKey('original_title');
});

it('traduce al idioma activo de la usuaria', function () {
    app()->setLocale('fr');

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The Hobbit',
                    'authors' => ['J. R. R. Tolkien'],
                    'language' => 'en',
                ],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'Le Hobbit'],
        ]),
    ]);

    $result = app(BookLookupService::class)->search('the hobbit');

    expect($result[0]['title'])->toBe('Le Hobbit')
        ->and($result[0]['original_title'])->toBe('The Hobbit');
});

it('no traduce si el libro ya está en el idioma activo', function () {
    app()->setLocale('en');

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The Hobbit',
                    'language' => 'en',
                ],
            ]],
        ]),
    ]);

    $result = app(BookLookupService::class)->search('the hobbit');

    expect($result[0]['title'])->toBe('The Hobbit')
        ->and($result[0])->not->toHaveKey('original_title');
});
