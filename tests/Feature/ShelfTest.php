<?php

use App\Models\Book;
use App\Models\Shelf;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('crea una estantería para el usuario autenticado', function () {
    $this->post(route('shelves.store'), ['name' => 'Favoritos'])
        ->assertRedirect();

    expect($this->user->shelves()->where('name', 'Favoritos')->exists())->toBeTrue();
});

it('no permite nombres vacíos', function () {
    $this->post(route('shelves.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('no permite modificar una estantería ajena', function () {
    $shelf = Shelf::factory()->for(User::factory())->create(['name' => 'Ajena']);

    $this->patch(route('shelves.update', $shelf), ['name' => 'Hackeada'])
        ->assertForbidden();
});

it('renombra una estantería propia', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['name' => 'Vieja']);

    $this->patch(route('shelves.update', $shelf), ['name' => 'Nueva'])
        ->assertRedirect();

    expect($shelf->fresh()->name)->toBe('Nueva');
});

it('reordena las estanterías del usuario', function () {
    $a = Shelf::factory()->for($this->user)->create(['name' => 'A', 'position' => 0]);
    $b = Shelf::factory()->for($this->user)->create(['name' => 'B', 'position' => 1]);

    $this->post(route('shelves.reorder'), ['ids' => [$b->id, $a->id]])
        ->assertRedirect();

    expect($a->fresh()->position)->toBe(1)
        ->and($b->fresh()->position)->toBe(0);
});

it('mueve los libros a Pendientes al eliminar una estantería', function () {
    $pendientes = Shelf::factory()->for($this->user)->create(['name' => 'Pendientes']);
    $otra = Shelf::factory()->for($this->user)->create(['name' => 'Otra']);
    $book = Book::factory()->for($this->user)->for($otra)->create();

    $this->delete(route('shelves.destroy', $otra))->assertRedirect();

    expect($book->fresh()->shelf_id)->toBe($pendientes->id);
});

it('coloca los libros al final del respaldo al eliminar una estantería', function () {
    $pendientes = Shelf::factory()->for($this->user)->create([
        'name' => 'Pendientes',
        'position' => 0,
    ]);
    $otra = Shelf::factory()->for($this->user)->create(['position' => 1]);

    $existente = Book::factory()->for($this->user)->for($pendientes)->create([
        'title' => 'Ya estaba',
        'position' => 0,
    ]);
    $primero = Book::factory()->for($this->user)->for($otra)->create([
        'title' => 'Primero',
        'position' => 0,
    ]);
    $segundo = Book::factory()->for($this->user)->for($otra)->create([
        'title' => 'Segundo',
        'position' => 1,
    ]);

    $this->delete(route('shelves.destroy', $otra))->assertRedirect();

    expect($primero->fresh()->position)->toBe(1)
        ->and($segundo->fresh()->position)->toBe(2)
        ->and($pendientes->books()->pluck('id')->all())->toBe([
            $existente->id,
            $primero->id,
            $segundo->id,
        ]);
});

it('cambia el modo de vista de una estantería', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['view_mode' => 'covers']);

    $this->patch(route('shelves.update', $shelf), ['name' => $shelf->name, 'view_mode' => 'spines'])
        ->assertRedirect();

    expect($shelf->fresh()->view_mode)->toBe('spines');
});
