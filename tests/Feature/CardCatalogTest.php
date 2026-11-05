<?php

use App\Models\Book;
use App\Models\User;
use App\Services\CardCatalogService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->catalog = app(CardCatalogService::class);
});

it('normaliza títulos con tildes y paréntesis', function () {
    expect($this->catalog->normalize('Alas de ónix (Empíreo 3)'))->toBe('alas de onix')
        ->and($this->catalog->normalize('  El día de la Trilla '))->toBe('el dia de la trilla');
});

it('activa la familia Empíreo con Alas de onix sin tilde', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de onix',
        'author' => 'Rebecca Yarros',
    ]);

    $pool = $this->catalog->poolFor($this->user);

    expect($pool)->toHaveCount(9)
        ->and($pool)->toContain('violet', 'xaden', 'tairn', 'andarna', 'bodhi');
});

it('activa la familia biblioteca con el coautor en el campo author', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'La biblioteca de la medianoche',
        'author' => 'Matt Haig, Miguel Marqués Muñoz',
    ]);

    expect($this->catalog->poolFor($this->user))->toContain('nora', 'mrs_elm', 'voltaire', 'ash');
});

it('activa la familia perfectos con el autor tal como está en la base', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'Mentiras y secretos',
        'author' => 'Álex Ramírez',
    ]);

    expect($this->catalog->poolFor($this->user))->toContain('jude', 'aegan', 'adrik', 'alex');
});

it('no duplica cartas cuando hay varios libros de la misma familia', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de sangre',
        'author' => 'Rebecca Yarros',
    ]);
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de hierro (Empíreo 2)',
        'author' => 'Rebecca Yarros',
    ]);

    expect($this->catalog->poolFor($this->user))->toHaveCount(9);
});

it('deja el pool vacío si ningún libro tiene cartas', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'Un libro cualquiera',
        'author' => 'Alguien',
    ]);

    expect($this->catalog->poolFor($this->user))->toBe([]);
});

it('devuelve metadatos completos para las 20 cartas', function () {
    foreach (array_keys(config('cards.cards')) as $key) {
        $meta = $this->catalog->metadata($key);

        expect($meta)->toHaveKeys(['key', 'family', 'familyLabel', 'familyEmblem', 'character', 'monogram', 'emblem'])
            ->and($meta['character'])->not->toBe('')
            ->and($meta['monogram'])->not->toBe('')
            ->and($meta['familyEmblem'])->not->toBe('')
            ->and($meta['emblem'])->not->toBe('');
    }
});
