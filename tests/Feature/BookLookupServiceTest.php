<?php

use App\Models\CatalogBook;
use App\Services\BookLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

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

it('normaliza los c�digos de idioma de Open Library antes de traducir', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([], 500),
        'openlibrary.org/search.json*' => Http::response([
            'docs' => [[
                'title' => 'The Housemaid',
                'language' => ['ger'],
                'author_name' => ['Freida McFadden'],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'La criada'],
            'responseStatus' => 200,
        ]),
    ]);

    app(BookLookupService::class)->search('the housemaid');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'mymemory')
        && $request['langpair'] === 'de|es');
});

it('conserva el t�tulo original si MyMemory rechaza el idioma de origen', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The Housemaid',
                    'language' => 'en',
                ],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => [
                'translatedText' => "'GE' IS AN INVALID SOURCE LANGUAGE . EXAMPLE: LANGPAIR=EN|IT USING 2 LETTER ISO OR RFC3066 LIKE ZH-CN. ALMOST ALL LANGUAGES SUPPORTED BUT SOME MAY HAVE NO CONTENT",
            ],
            'responseStatus' => 403,
        ]),
    ]);

    $result = app(BookLookupService::class)->search('the housemaid');

    expect($result[0]['title'])->toBe('The Housemaid')
        ->and($result[0])->not->toHaveKey('original_title');
});

it('busca en Google Books en el idioma activo', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response(['items' => []]),
        'openlibrary.org/*' => Http::response(['docs' => []]),
    ]);

    app(BookLookupService::class)->search('the hobbit');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'googleapis')
        && $request['langRestrict'] === 'es');
});

it('usa el título oficial de la edición española de Open Library', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([], 500),
        'openlibrary.org/search.json*' => Http::response([
            'docs' => [[
                'key' => '/works/OL27729743W',
                'title' => 'The Housemaid',
                'language' => ['eng'],
                'author_name' => ['Freida McFadden'],
            ]],
        ]),
        'openlibrary.org/works/OL27729743W/editions.json*' => Http::response([
            'entries' => [
                [
                    'title' => 'The Housemaid',
                    'languages' => [['key' => '/languages/eng']],
                ],
                [
                    'title' => 'La empleada',
                    'languages' => [['key' => '/languages/spa']],
                    'publish_date' => '2023',
                ],
            ],
        ]),
    ]);

    $result = app(BookLookupService::class)->search('the housemaid');

    expect($result[0]['title'])->toBe('La empleada')
        ->and($result[0]['original_title'])->toBe('The Housemaid')
        ->and($result[0]['language'])->toBe('es');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'mymemory'));
});

it('elige el título español más frecuente entre las ediciones', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([], 500),
        'openlibrary.org/search.json*' => Http::response([
            'docs' => [[
                'key' => '/works/OL14860424W',
                'title' => 'Rayuela',
                'language' => ['heb'],
                'author_name' => ['Julio Cortázar'],
            ]],
        ]),
        'openlibrary.org/works/OL14860424W/editions.json*' => Http::response([
            'entries' => [
                [
                    'title' => 'Rayuela',
                    'languages' => [['key' => '/languages/spa']],
                    'publish_date' => '2016',
                ],
                [
                    'title' => 'Rayuela',
                    'languages' => [['key' => '/languages/spa']],
                    'publish_date' => '1999',
                ],
                [
                    'title' => 'Hopscotch',
                    'languages' => [['key' => '/languages/spa']],
                    'publish_date' => '2020',
                ],
            ],
        ]),
    ]);

    $result = app(BookLookupService::class)->search('rayuela');

    expect($result[0]['title'])->toBe('Rayuela')
        ->and($result[0])->not->toHaveKey('original_title');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'mymemory'));
});

it('devuelve las fichas locales antes que los resultados de la api', function () {
    $local = CatalogBook::factory()->create([
        'title' => 'Rayuela local',
        'isbn' => '9788437604947',
    ]);

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [['volumeInfo' => ['title' => 'Rayuela API', 'language' => 'es']]],
        ]),
    ]);

    $results = app(BookLookupService::class)->search('rayuela');

    expect($results[0]['title'])->toBe('Rayuela local')
        ->and($results[0]['catalog_book_id'])->toBe($local->id)
        ->and($results[1]['title'])->toBe('Rayuela API');
});

it('devuelve fichas locales aunque las apis fallen', function () {
    CatalogBook::factory()->create(['title' => 'Rayuela local']);

    Http::fake(fn () => throw new ConnectionException('fallo'));

    $results = app(BookLookupService::class)->search('rayuela');

    expect($results)->toHaveCount(1)
        ->and($results[0]['title'])->toBe('Rayuela local');
});

it('encuentra fichas locales por isbn', function () {
    $local = CatalogBook::factory()->create([
        'title' => 'Rayuela local',
        'isbn' => '9788437604947',
    ]);

    Http::fake([
        'www.googleapis.com/*' => Http::response(['items' => []]),
        'openlibrary.org/*' => Http::response(['docs' => []]),
    ]);

    $results = app(BookLookupService::class)->byIsbn('978-84-376-0494-7');

    expect($results[0]['catalog_book_id'])->toBe($local->id)
        ->and($results[0]['title'])->toBe('Rayuela local');
});

it('no traduce las fichas locales', function () {
    CatalogBook::factory()->create(['title' => 'The Local Book']);

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The API Book',
                    'language' => 'en',
                ],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'El libro API'],
            'responseStatus' => 200,
        ]),
    ]);

    $results = app(BookLookupService::class)->search('book');

    expect($results[0]['title'])->toBe('The Local Book')
        ->and($results[1]['title'])->toBe('El libro API');
});
