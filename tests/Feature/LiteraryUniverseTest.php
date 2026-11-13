<?php

use App\Models\Book;
use App\Models\LiteraryUniverse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('lista los universos literarios del usuario', function () {
    $user = User::factory()->master()->create();
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

it('expone los libros de cada universo en orden de lectura con su valoración', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create(['name' => 'Saga A']);
    LiteraryUniverse::factory()->for($user)->create(['name' => 'Saga B']);
    $first = Book::factory()->for($user)->create(['title' => 'Primero', 'rating' => 5]);
    $second = Book::factory()->for($user)->create(['title' => 'Segundo', 'rating' => 3]);

    $universe->books()->attach($first->id, ['reading_order' => 2]);
    $universe->books()->attach($second->id, ['reading_order' => 1]);

    $this->actingAs($user)
        ->get(route('literary-universes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LiteraryUniverses/Index')
            ->where('universes.0.name', 'Saga A')
            ->has('universes.0.books', 2)
            ->where('universes.0.books.0.title', 'Segundo')
            ->where('universes.0.books.0.reading_order', 1)
            ->where('universes.0.books.0.rating', 3)
            ->where('universes.0.books.1.title', 'Primero')
            ->where('universes.0.books.1.reading_order', 2)
            ->where('universes.0.books.1.rating', 5)
            ->has('universes.1.books', 0));
});

it('muestra al usuario los universos del master y el suyo propio', function () {
    $user = User::factory()->create();
    $master = User::factory()->master()->create();
    LiteraryUniverse::factory()->for($master)->create(['name' => 'Del master']);
    LiteraryUniverse::factory()->for($user)->create(['name' => 'Mío']);
    LiteraryUniverse::factory()->for(User::factory())->create(['name' => 'De otra persona']);

    $this->actingAs($user)
        ->get(route('literary-universes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('universes', 2)
            ->where('universes.0.name', 'Del master')
            ->where('universes.1.name', 'Mío')
            ->where('canManage', false));
});

it('crea un universo literario', function () {
    $user = User::factory()->master()->create();

    $response = $this->actingAs($user)->post(route('literary-universes.store'), [
        'name' => 'Canción de Hielo y Fuego',
        'description' => 'Saga épica de fantasía',
        'type' => 'saga',
    ]);

    $universe = LiteraryUniverse::firstOrFail();

    $response->assertRedirect(route('literary-universes.show', $universe))
        ->assertSessionHas('success', __('ui.universe_created'));

    expect($universe->user_id)->toBe($user->id);
    expect($universe->name)->toBe('Canción de Hielo y Fuego');
    expect($universe->type)->toBe('saga');
});

it('abre el modal de agregar libros tras crear la colección', function () {
    $user = User::factory()->master()->create();

    $this->actingAs($user)->post(route('literary-universes.store'), [
        'name' => 'Nueva colección',
        'type' => 'trilogy',
        'add' => 1,
    ])->assertSessionHas('openAdd', true);

    $universe = LiteraryUniverse::firstOrFail();

    $this->actingAs($user)
        ->get(route('literary-universes.show', $universe))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('openAdd', true));
});

it('prohíbe crear un universo sin nombre', function () {
    $user = User::factory()->master()->create();

    $this->actingAs($user)->post(route('literary-universes.store'), [
        'name' => '',
        'type' => 'saga',
    ])->assertSessionHasErrors('name');

    expect(LiteraryUniverse::count())->toBe(0);
});

it('edita un universo propio', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create(['name' => 'Antes']);

    $this->actingAs($user)->patch(route('literary-universes.update', $universe), [
        'name' => 'Después',
        'description' => 'Nueva desc',
        'type' => 'trilogy',
    ])->assertRedirect(route('literary-universes.index'));

    expect($universe->fresh()->name)->toBe('Después');
    expect($universe->fresh()->type)->toBe('trilogy');
});

it('no permite editar a quien no es master', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for(User::factory()->master())->create();

    $this->actingAs($user)->patch(route('literary-universes.update', $universe), [
        'name' => 'Intento',
        'type' => 'saga',
    ])->assertForbidden();
});

it('elimina un universo propio', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('literary-universes.destroy', $universe))
        ->assertRedirect(route('literary-universes.index'));

    expect(LiteraryUniverse::find($universe->id))->toBeNull();
});

it('no permite eliminar a quien no es master', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for(User::factory()->master())->create();

    $this->actingAs($user)->delete(route('literary-universes.destroy', $universe))
        ->assertForbidden();
});

it('muestra el detalle de un universo con sus libros ordenados', function () {
    $user = User::factory()->master()->create();
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

it('devuelve los libros propios marcando los que ya están en la colección', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $linked = Book::factory()->for($user)->create(['title' => 'Dentro']);
    Book::factory()->for($user)->create(['title' => 'Fuera']);
    Book::factory()->for(User::factory())->create(['title' => 'Ajeno']);

    $universe->books()->attach($linked->id, ['reading_order' => 1]);

    $this->actingAs($user)
        ->getJson(route('literary-universes.books.available', $universe))
        ->assertOk()
        ->assertJsonCount(2, 'books')
        ->assertJsonFragment([
            'title' => 'Dentro',
            'in_collection' => true,
            'reading_order' => 1,
        ])
        ->assertJsonFragment([
            'title' => 'Fuera',
            'in_collection' => false,
        ]);
});

it('filtra los libros disponibles por título o autor', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    Book::factory()->for($user)->create(['title' => 'El hobbit', 'author' => 'Tolkien']);
    Book::factory()->for($user)->create(['title' => 'Dune', 'author' => 'Herbert']);

    $this->actingAs($user)
        ->getJson(route('literary-universes.books.available', ['universe' => $universe, 'q' => 'tolkien']))
        ->assertOk()
        ->assertJsonCount(1, 'books')
        ->assertJsonPath('books.0.title', 'El hobbit');
});

it('prohíbe los libros disponibles a quien no es master', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for(User::factory()->master())->create();

    $this->actingAs($user)
        ->getJson(route('literary-universes.books.available', $universe))
        ->assertForbidden();
});

it('no permite ver el detalle de un universo de otro usuario que no es master', function () {
    $user = User::factory()->create();
    $universe = LiteraryUniverse::factory()->for(User::factory())->create();

    $this->actingAs($user)->get(route('literary-universes.show', $universe))
        ->assertForbidden();
});

it('muestra a cualquier usuario los libros que el master añadió a la colección', function () {
    $user = User::factory()->create();
    $master = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($master)->create(['name' => 'Trilogía']);
    $book = Book::factory()->for($master)->create(['title' => 'La comunidad del anillo']);
    $universe->books()->attach($book->id, ['reading_order' => 1]);

    $this->actingAs($user)
        ->get(route('literary-universes.show', $universe))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('universe.name', 'Trilogía')
            ->has('books', 1)
            ->where('books.0.title', 'La comunidad del anillo')
            ->where('books.0.reading_order', 1)
            ->where('canManage', false));
});

it('no permite crear un universo a quien no es master', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('literary-universes.store'), [
        'name' => 'No permitido',
        'type' => 'saga',
    ])->assertForbidden();

    expect(LiteraryUniverse::count())->toBe(0);
});

it('prohíbe agregar libros a un universo a quien no es master', function () {
    $user = User::factory()->create();
    $master = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($master)->create();
    $book = Book::factory()->for($master)->create();

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_ids' => [$book->id],
    ])->assertForbidden();

    expect($universe->fresh()->books()->count())->toBe(0);
});

it('agrega un libro a un universo', function () {
    $user = User::factory()->master()->create();
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
    $user = User::factory()->master()->create();
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
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book = Book::factory()->for(User::factory())->create();

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_id' => $book->id,
        'reading_order' => 1,
    ])->assertForbidden();

    expect($universe->fresh()->books()->count())->toBe(0);
});

it('agrega varios libros a la vez respetando el orden recibido', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $existing = Book::factory()->for($user)->create();
    $first = Book::factory()->for($user)->create();
    $second = Book::factory()->for($user)->create();

    $universe->books()->attach($existing->id, ['reading_order' => 1]);

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_ids' => [$second->id, $first->id],
    ])->assertRedirect();

    $orders = $universe->fresh()->universeBooks()->pluck('reading_order', 'book_id');

    expect($universe->fresh()->books()->count())->toBe(3)
        ->and($orders[$existing->id])->toBe(1)
        ->and($orders[$second->id])->toBe(2)
        ->and($orders[$first->id])->toBe(3);
});

it('no agrega en bloque libros que ya están en la colección', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $linked = Book::factory()->for($user)->create();
    $free = Book::factory()->for($user)->create();

    $universe->books()->attach($linked->id, ['reading_order' => 1]);

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_ids' => [$free->id, $linked->id],
    ])->assertSessionHasErrors('book_ids');

    expect($universe->fresh()->books()->count())->toBe(1);
});

it('no agrega en bloque ningún libro si uno es de otro usuario', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $own = Book::factory()->for($user)->create();
    $foreign = Book::factory()->for(User::factory())->create();

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_ids' => [$own->id, $foreign->id],
    ])->assertForbidden();

    expect($universe->fresh()->books()->count())->toBe(0);
});

it('rechaza ids repetidos en el alta múltiple', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book = Book::factory()->for($user)->create();

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_ids' => [$book->id, $book->id],
    ])->assertSessionHasErrors();

    expect($universe->fresh()->books()->count())->toBe(0);
});

it('completa el flujo de crear, agregar, ordenar, quitar y persistir', function () {
    $user = User::factory()->master()->create();
    $books = Book::factory()->count(3)->for($user)->create();

    $response = $this->actingAs($user)->post(route('literary-universes.store'), [
        'name' => 'El Señor de los Anillos',
        'type' => 'trilogy',
    ]);

    $universe = LiteraryUniverse::where('user_id', $user->id)->firstOrFail();

    $response->assertRedirect(route('literary-universes.show', $universe));

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_ids' => [$books[2]->id, $books[0]->id],
    ])->assertRedirect();

    $this->actingAs($user)->getJson(route('literary-universes.books.available', $universe))
        ->assertOk()
        ->assertJsonCount(3, 'books');

    $this->actingAs($user)->patch(route('literary-universes.reorder', $universe), [
        'orders' => [
            $books[0]->id => 1,
            $books[2]->id => 2,
        ],
    ])->assertRedirect();

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_ids' => [$books[1]->id],
    ])->assertRedirect();

    $this->actingAs($user)->post(route('literary-universes.books.store', $universe), [
        'book_ids' => [$books[2]->id],
    ])->assertSessionHasErrors('book_ids');

    $this->actingAs($user)->delete(route('literary-universes.books.destroy', [$universe, $books[0]]))
        ->assertRedirect();

    $final = $universe->fresh()->universeBooks()->get()->sortBy('reading_order')->values();

    expect($final->pluck('book_id')->all())->toBe([$books[2]->id, $books[1]->id])
        ->and($final->pluck('reading_order')->all())->toBe([1, 2]);

    $this->actingAs($user)->get(route('literary-universes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('universes.0.books', 2)
            ->where('universes.0.total_books', 2)
            ->where('universes.0.books.0.id', $books[2]->id)
            ->where('universes.0.books.0.reading_order', 1)
            ->where('universes.0.books.1.id', $books[1]->id)
            ->where('universes.0.books.1.reading_order', 2));
});

it('quita un libro de un universo', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book = Book::factory()->for($user)->create();
    $universe->books()->attach($book->id, ['reading_order' => 1]);

    $this->actingAs($user)->delete(route('literary-universes.books.destroy', [$universe, $book]))
        ->assertRedirect()
        ->assertSessionHas('success', __('ui.universe_book_removed'));

    expect($universe->fresh()->books()->count())->toBe(0);
});

it('reordena los libros de un universo', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book1 = Book::factory()->for($user)->create();
    $book2 = Book::factory()->for($user)->create();
    $book3 = Book::factory()->for($user)->create();

    $universe->books()->attach($book1->id, ['reading_order' => 1]);
    $universe->books()->attach($book2->id, ['reading_order' => 2]);
    $universe->books()->attach($book3->id, ['reading_order' => 3]);

    $this->actingAs($user)->patch(route('literary-universes.reorder', $universe), [
        'orders' => [
            $book3->id => 1,
            $book1->id => 2,
            $book2->id => 3,
        ],
    ])->assertRedirect()
        ->assertSessionHas('success', __('ui.universe_reordered'));

    $orders = $universe->fresh()->universeBooks->pluck('reading_order', 'book_id')->all();
    expect($orders[$book3->id])->toBe(1);
    expect($orders[$book1->id])->toBe(2);
    expect($orders[$book2->id])->toBe(3);
});

it('no permite reordenar con IDs incompletos', function () {
    $user = User::factory()->master()->create();
    $universe = LiteraryUniverse::factory()->for($user)->create();
    $book1 = Book::factory()->for($user)->create();
    $universe->books()->attach($book1->id, ['reading_order' => 1]);

    $this->actingAs($user)->patch(route('literary-universes.reorder', $universe), [
        'orders' => [99999 => 1],
    ])->assertSessionHasErrors('orders');

    expect($universe->fresh()->universeBooks->first()->reading_order)->toBe(1);
});
