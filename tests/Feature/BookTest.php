<?php

use App\Models\Book;
use App\Models\Shelf;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('crea un libro manual en una estantería propia', function () {
    $shelf = Shelf::factory()->for($this->user)->create();

    $this->post(route('books.store'), [
        'title' => 'Cien años de soledad',
        'author' => 'Gabriel García Márquez',
        'shelf_id' => $shelf->id,
        'rating' => 5,
        'notes' => 'Una relectura pendiente.',
    ])->assertRedirect();

    expect($this->user->books()->where('title', 'Cien años de soledad')->exists())->toBeTrue();
});

it('no permite guardar en una estantería ajena', function () {
    $shelf = Shelf::factory()->for(User::factory())->create();

    $this->post(route('books.store'), [
        'title' => 'Intruso',
        'shelf_id' => $shelf->id,
    ])->assertForbidden();
});

it('mueve un libro a otra estantería propia', function () {
    $from = Shelf::factory()->for($this->user)->create();
    $to = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($from)->create();

    $this->patch(route('books.move', $book), ['shelf_id' => $to->id])
        ->assertRedirect();

    expect($book->fresh()->shelf_id)->toBe($to->id);
});

it('no permite mover un libro ajeno', function () {
    $book = Book::factory()->for(User::factory())->create();
    $shelf = Shelf::factory()->for($this->user)->create();

    $this->patch(route('books.move', $book), ['shelf_id' => $shelf->id])
        ->assertForbidden();
});

it('actualiza un libro propio', function () {
    $book = Book::factory()->for($this->user)->create(['title' => 'Viejo']);

    $this->patch(route('books.update', $book), [
        'title' => 'Nuevo',
        'rating' => 4,
    ])->assertRedirect();

    expect($book->fresh()->title)->toBe('Nuevo')
        ->and($book->fresh()->rating)->toBe(4);
});

it('elimina un libro propio', function () {
    $book = Book::factory()->for($this->user)->create();

    $this->delete(route('books.destroy', $book))->assertRedirect();

    expect(Book::find($book->id))->toBeNull();
});
