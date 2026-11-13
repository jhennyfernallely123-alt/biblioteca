<?php

use App\Models\Book;
use App\Models\LiteraryUniverse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('lista los universos literarios del usuario', function () {
    $user = User::factory()->create();
    LiteraryUniverse::factory()->for($user)->create(['name' => 'Saga A']);
    LiteraryUniverse::factory()->for($user)->create(['name' => 'Saga B']);
    LiteraryUniverse::factory()->for(User::factory())->create(['name' => 'Ajena']);

    $this->actingAs($user)
        ->get(route('literary-universes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LiteraryUniverses/Index')
            ->has('universes', 2));
});

it('no muestra universos de otros usuarios', function () {
    $user = User::factory()->create();
    LiteraryUniverse::factory()->for($user)->create(['name' => 'Mía']);
    LiteraryUniverse::factory()->for(User::factory())->create(['name' => 'Ajena']);

    $this->actingAs($user)
        ->get(route('literary-universes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('universes.0.name', 'Mía'));
});

it('crea un universo literario', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('literary-universes.store'), [
        'name' => 'Canción de Hielo y Fuego',
        'description' => 'Saga épica de fantasía',
        'type' => 'saga',
    ])->assertRedirect(route('literary-universes.index'))
        ->assertSessionHas('success', __('ui.universe_created'));

    expect(LiteraryUniverse::where('user_id', $user->id)->count())->toBe(1);
    expect(LiteraryUniverse::first()->name)->toBe('Canción de Hielo y Fuego');
    expect(LiteraryUniverse::first()->type)->toBe('saga');
});

it('prohíbe crear un universo sin nombre', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('literary-universes.store'), [
        'name' => '',
        'type' => 'saga',
    ])->assertSessionHasErrors('name');

    expect(LiteraryUniverse::count())->toBe(0);
});

it('edita un universo propio', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create(['name' => 'Antes']);

    $this->actingAs($user)->patch(route('literary-universes.update', $universe), [
        'name' => 'Después',
        'description' => 'Nueva desc',
        'type' => 'trilogy',
    ])->assertRedirect(route('literary-universes.index'));

    expect($universe->fresh()->name)->toBe('Después');
    expect($universe->fresh()->type)->toBe('trilogy');
});

it('no permite editar un universo ajeno', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for(User::factory())->create();

    $this->actingAs($user)->patch(route('literary-universes.update', $universe), [
        'name' => 'Intento',
        'type' => 'saga',
    ])->assertForbidden();
});

it('elimina un universo propio', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('literary-universes.destroy', $universe))
        ->assertRedirect(route('literary-universes.index'));

    expect(LiteraryUniverse::find($universe->id))->toBeNull();
});

it('no permite eliminar un universo ajeno', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for(User::factory())->create();

    $this->actingAs($user)->delete(route('literary-universes.destroy', $universe))
        ->assertForbidden();
});

it('muestra el detalle de un universo con sus libros ordenados', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book1 = Book::factory()->for($user)->create(['title' => 'Libro 1']);
    $book2 = Book::factory()->for($user)->create(['title' => 'Libro 2']);
    $book3 = Book::factory()->for($user)->create(['title' => 'Libro 3']);

    $universe->books()->attach($book1->id, ['reading_order' => 1]);
    $universe->books()->attach($book2->id, ['reading_order' => 3]);
    $universe->books()->attach($book3->id, ['reading_order' => 2]);

    $this->actingAs($user)
        ->get(route('literary-universes.show', $universe))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LiteraryUniverses/Show')
            ->where('universe.name', $universe->name)
            ->has('books', 3)
            ->where('books.0.reading_order', 1)
            ->where('books.0.title', 'Libro 1')
            ->where('books.1.reading_order', 2)
            ->where('books.1.title', 'Libro 3')
            ->where('books.2.reading_order', 3)
            ->where('books.2.title', 'Libro 2'));
});

it('no permite ver el detalle de un universo ajeno', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for(User::factory())->create();

    $this->actingAs($user)->get(route('literary-universes.show', $universe))
        ->assertForbidden();
});

it('agrega un libro a un universo', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book = Book::factory()->for($user)->create();

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_id' => $book->id,
        'reading_order' => 1,
    ])->assertRedirect()
        ->assertSessionHas('success', __('ui.universe_book_added'));

    expect($universe->fresh()->books()->whereKey($book->id)->exists())->toBeTrue();
    expect($universe->books()->whereKey($book->id)->first()->pivot->reading_order)->toBe(1);
});

it('no permite agregar un libro ya existente en el universo', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book = Book::factory()->for($user)->create();

    $universe->books()->attach($book->id, ['reading_order' => 1]);

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_id' => $book->id,
        'reading_order' => 2,
    ])->assertSessionHasErrors('book_id');

    expect($universe->fresh()->books()->count())->toBe(1);
});

it('no permite agregar un libro ajeno al universo', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book = Book::factory()->for(User::factory())->create();

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_id' => $book->id,
        'reading_order' => 1,
    ])->assertForbidden();

    expect($universe->fresh()->books()->count())->toBe(0);
});

it('quita un libro de un universo', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book = Book::factory()->for($user)->create();
    $universe->books()->attach($book->id, ['reading_order' => 1]);

    $this->actingAs($user)->delete(route('literary-universes.books.destroy', [$universe, $book]))
        ->assertRedirect()
        ->assertSessionHas('success', __('ui.universe_book_removed'));

    expect($universe->fresh()->books()->count())->toBe(0);
});

it('reordena los libros de un universo', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book1 = Book::factory()->for($user)->create();
    $book2 = Book::factory()->for($user)->create();
    $book3 = Book::factory()->for($user)->create();

    $ub1 = $universe->books()->attach($book1->id, ['reading_order' => 1]);
    $ub2 = $universe->books()->attach($book2->id, ['reading_order' => 2]);
    $ub3 = $universe->books()->attach($book3->id, ['reading_order' => 3]);

    $this->actingAs($user)->patch(route('literary-universes.reorder', $universe), [
        'orders' => [
            $ub3->id => 1,
            $ub1->id => 2,
            $ub2->id => 3,
        ],
    ])->assertRedirect()
        ->assertSessionHas('success', __('ui.universe_reordered'));

    $orders = $universe->fresh()->universeBooks->pluck('reading_order', 'id')->all();
    expect($orders[$ub3->id])->toBe(1);
    expect($orders[$ub1->id])->toBe(2);
    expect($orders[$ub2->id])->toBe(3);
});

it('no permite reordenar con IDs incompletos', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book1 = Book::factory()->for($user)->create();
    $ub1 = $universe->books()->attach($book1->id, ['reading_order' => 1]);

    $this->actingAs($user)->patch(route('literary-universes.reorder', $universe), [
        'orders' => [99999 => 1],
    ])->assertSessionHasErrors('orders');

    expect($universe->fresh()->universeBooks->whereKey($ub1->id)->first()->pivot->reading_order)->toBe(1);
});
