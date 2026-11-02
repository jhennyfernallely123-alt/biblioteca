<?php

use App\Models\Book;
use App\Models\Decoration;
use App\Models\Shelf;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('reúne todos los libros en la biblioteca ordenados por estantería', function () {
    $first = Shelf::factory()->for($this->user)->create(['position' => 0]);
    $second = Shelf::factory()->for($this->user)->create(['position' => 1]);
    $later = Book::factory()->for($this->user)->for($second)->create(['position' => 0]);
    $early = Book::factory()->for($this->user)->for($first)->create(['position' => 0]);

    $this->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Library/Index')
            ->has('books', 2)
            ->where('books.0.id', $early->id)
            ->where('books.1.id', $later->id)
            ->where('books.0.shelf_id', $first->id));
});

it('no mezcla libros de otra cuenta en la biblioteca', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    Book::factory()->for($this->user)->for($shelf)->create();
    Book::factory()->for(User::factory())->create();

    $this->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('books', 1));
});

it('entrega todas las decoraciones propias en la biblioteca', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    Decoration::factory()->for($this->user)->for($shelf)->create(['kind' => 'cup']);

    $this->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('decorations', 1)
            ->where('decorations.0.kind', 'cup'));
});

it('mantiene las estanterías en su propio módulo', function () {
    Shelf::factory()->for($this->user)->create(['name' => 'Pendientes'])
        ->books()
        ->save(Book::factory()->for($this->user)->make());

    $this->get(route('shelves.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Shelves/Index')
            ->has('shelves', 1)
            ->where('shelves.0.name', 'Pendientes'));
});
