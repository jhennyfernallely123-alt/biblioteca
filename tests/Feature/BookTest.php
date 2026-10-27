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

it('no permite mover un libro propio a una estantería ajena', function () {
    $book = Book::factory()->for($this->user)->create();
    $shelf = Shelf::factory()->for(User::factory())->create();

    $this->patch(route('books.move', $book), ['shelf_id' => $shelf->id])
        ->assertForbidden();

    expect($book->fresh()->shelf_id)->not->toBe($shelf->id);
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

it('ordena los libros por posición manual y usa el título como desempate', function () {
    $shelf = Shelf::factory()->for($this->user)->create();

    Book::factory()->for($this->user)->for($shelf)->create([
        'title' => 'Alfa',
        'position' => 1,
    ]);
    Book::factory()->for($this->user)->for($shelf)->create([
        'title' => 'Zeta',
        'position' => 0,
    ]);

    expect($shelf->books()->pluck('title')->all())->toBe(['Zeta', 'Alfa']);
});

it('coloca un libro nuevo al final de su estantería', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    Book::factory()->for($this->user)->for($shelf)->create(['position' => 0]);
    Book::factory()->for($this->user)->for($shelf)->create(['position' => 1]);

    $this->post(route('books.store'), [
        'title' => 'Nuevo',
        'shelf_id' => $shelf->id,
    ])->assertRedirect();

    expect($this->user->books()->where('title', 'Nuevo')->first()->position)->toBe(2);
});

it('coloca al final un libro movido a otra estantería', function () {
    $from = Shelf::factory()->for($this->user)->create();
    $to = Shelf::factory()->for($this->user)->create();
    Book::factory()->for($this->user)->for($to)->create(['position' => 0]);
    $book = Book::factory()->for($this->user)->for($from)->create(['position' => 4]);

    $this->patch(route('books.move', $book), ['shelf_id' => $to->id])
        ->assertRedirect();

    expect($book->fresh()->position)->toBe(1);
});

it('coloca al final un libro al cambiar de estantería desde la ficha', function () {
    $from = Shelf::factory()->for($this->user)->create();
    $to = Shelf::factory()->for($this->user)->create();
    Book::factory()->for($this->user)->for($to)->create(['position' => 0]);
    $book = Book::factory()->for($this->user)->for($from)->create([
        'title' => 'Mudanza',
        'position' => 0,
    ]);

    $this->patch(route('books.update', $book), [
        'title' => 'Mudanza',
        'shelf_id' => $to->id,
    ])->assertRedirect();

    expect($book->fresh()->shelf_id)->toBe($to->id)
        ->and($book->fresh()->position)->toBe(1);
});

it('reordena los libros de una estantería propia', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $a = Book::factory()->for($this->user)->for($shelf)->create(['title' => 'A', 'position' => 0]);
    $b = Book::factory()->for($this->user)->for($shelf)->create(['title' => 'B', 'position' => 1]);
    $c = Book::factory()->for($this->user)->for($shelf)->create(['title' => 'C', 'position' => 2]);

    $this->patch(route('books.reorder'), [
        'shelf_id' => $shelf->id,
        'ids' => [$c->id, $a->id, $b->id],
    ])->assertRedirect();

    expect($shelf->books()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('no permite reordenar una estantería ajena', function () {
    $shelf = Shelf::factory()->for(User::factory())->create();
    $book = Book::factory()->for($shelf->user)->for($shelf)->create();

    $this->patch(route('books.reorder'), [
        'shelf_id' => $shelf->id,
        'ids' => [$book->id],
    ])->assertForbidden();
});

it('rechaza una lista que no contiene todos los libros de la estantería', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $a = Book::factory()->for($this->user)->for($shelf)->create();
    Book::factory()->for($this->user)->for($shelf)->create();

    $this->patchJson(route('books.reorder'), [
        'shelf_id' => $shelf->id,
        'ids' => [$a->id],
    ])->assertStatus(422);
});

it('rechaza IDs repetidos al reordenar', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($shelf)->create();

    $this->patch(route('books.reorder'), [
        'shelf_id' => $shelf->id,
        'ids' => [$book->id, $book->id],
    ])->assertSessionHasErrors('ids.1');
});
