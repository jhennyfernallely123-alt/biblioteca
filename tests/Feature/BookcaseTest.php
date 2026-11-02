<?php

use App\Models\Book;
use App\Models\Shelf;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->shelf = Shelf::factory()->for($this->user)->create();
    $this->actingAs($this->user);
});

it('mueve un libro a otra banda y empuja el sobrante a la siguiente', function () {
    $books = collect(range(0, 5))->map(fn (int $index) => Book::factory()
        ->for($this->user)
        ->for($this->shelf)
        ->create([
            'title' => "Libro {$index}",
            'band' => $index < 5 ? 0 : 1,
            'band_position' => $index < 5 ? $index : 0,
        ]));

    $this->patch(route('bookcase.move'), [
        'book_id' => $books[5]->id,
        'band' => 0,
        'position' => 0,
    ])->assertRedirect();

    expect($books[5]->fresh()->band)->toBe(0)
        ->and($books[5]->fresh()->band_position)->toBe(0)
        ->and($books[0]->fresh()->band)->toBe(0)
        ->and($books[0]->fresh()->band_position)->toBe(1)
        ->and($books[4]->fresh()->band)->toBe(1)
        ->and($books[4]->fresh()->band_position)->toBe(0);
});

it('encadena la cascada por varias bandas', function () {
    $books = collect(range(0, 10))->map(fn (int $index) => Book::factory()
        ->for($this->user)
        ->for($this->shelf)
        ->create([
            'title' => "Libro {$index}",
            'band' => intdiv($index, 5),
            'band_position' => $index % 5,
        ]));

    $this->patch(route('bookcase.move'), [
        'book_id' => $books[10]->id,
        'band' => 0,
        'position' => 0,
    ])->assertRedirect();

    expect($books[10]->fresh()->band)->toBe(0)
        ->and($books[4]->fresh()->band)->toBe(1)
        ->and($books[4]->fresh()->band_position)->toBe(0)
        ->and($books[9]->fresh()->band)->toBe(2)
        ->and($books[9]->fresh()->band_position)->toBe(0);
});

it('al soltar al final de una banda llena baja el último libro anterior', function () {
    $books = collect(range(0, 5))->map(fn (int $index) => Book::factory()
        ->for($this->user)
        ->for($this->shelf)
        ->create([
            'title' => "Libro {$index}",
            'band' => $index < 5 ? 0 : 1,
            'band_position' => $index < 5 ? $index : 0,
        ]));

    $this->patch(route('bookcase.move'), [
        'book_id' => $books[5]->id,
        'band' => 0,
        'position' => 5,
    ])->assertRedirect();

    expect($books[5]->fresh()->band)->toBe(0)
        ->and($books[5]->fresh()->band_position)->toBe(4)
        ->and($books[4]->fresh()->band)->toBe(1)
        ->and($books[4]->fresh()->band_position)->toBe(0);
});

it('deja menos libros en la banda de origen sin subir los de la siguiente', function () {
    $first = Book::factory()->for($this->user)->for($this->shelf)
        ->create(['band' => 0, 'band_position' => 0, 'title' => 'A']);
    $second = Book::factory()->for($this->user)->for($this->shelf)
        ->create(['band' => 0, 'band_position' => 1, 'title' => 'B']);
    $third = Book::factory()->for($this->user)->for($this->shelf)
        ->create(['band' => 0, 'band_position' => 2, 'title' => 'C']);
    $other = Book::factory()->for($this->user)->for($this->shelf)
        ->create(['band' => 1, 'band_position' => 0, 'title' => 'D']);

    $this->patch(route('bookcase.move'), [
        'book_id' => $first->id,
        'band' => 1,
        'position' => 0,
    ])->assertRedirect();

    expect($second->fresh()->band)->toBe(0)
        ->and($second->fresh()->band_position)->toBe(0)
        ->and($third->fresh()->band)->toBe(0)
        ->and($third->fresh()->band_position)->toBe(1)
        ->and($first->fresh()->band)->toBe(1)
        ->and($other->fresh()->band)->toBe(1)
        ->and($other->fresh()->band_position)->toBe(1);
});

it('no permite mover un libro ajeno de banda', function () {
    $foreign = Book::factory()->for(User::factory())->create();

    $this->patch(route('bookcase.move'), [
        'book_id' => $foreign->id,
        'band' => 0,
        'position' => 0,
    ])->assertNotFound();
});
