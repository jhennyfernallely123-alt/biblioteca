<?php

use App\Models\Book;
use App\Models\Decoration;
use App\Models\Shelf;
use App\Models\User;
use Inertia\Middleware as InertiaMiddleware;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('guarda objetos con su estantería y su ancla', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($shelf)->create();

    $decoration = $this->user->decorations()->create([
        'shelf_id' => $shelf->id,
        'kind' => 'cup',
        'color' => 'rose',
        'after_book_id' => $book->id,
        'sort' => 0,
    ]);

    expect($decoration->fresh()->kind)->toBe('cup')
        ->and($decoration->shelf->id)->toBe($shelf->id)
        ->and($decoration->afterBook->id)->toBe($book->id);
});

it('ordena los objetos de una estantería por sort y luego por id', function () {
    $shelf = Shelf::factory()->for($this->user)->create();

    $second = Decoration::factory()->for($this->user)->for($shelf)->create(['sort' => 1]);
    $first = Decoration::factory()->for($this->user)->for($shelf)->create(['sort' => 0]);

    expect($shelf->decorations()->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('borra los objetos al borrar la estantería', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $decoration = Decoration::factory()->for($this->user)->for($shelf)->create();

    $shelf->delete();

    expect(Decoration::find($decoration->id))->toBeNull();
});

it('deja sin ancla el objeto al borrar el libro', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($shelf)->create();
    $decoration = Decoration::factory()->for($this->user)->for($shelf)->create([
        'after_book_id' => $book->id,
    ]);

    $book->delete();

    expect($decoration->fresh()->after_book_id)->toBeNull();
});

it('añade un objeto al final de la primera estantería', function () {
    $first = Shelf::factory()->for($this->user)->create(['name' => 'Primera', 'position' => 0]);
    Shelf::factory()->for($this->user)->create(['name' => 'Segunda', 'position' => 1]);
    $last = Book::factory()->for($this->user)->for($first)->create(['title' => 'Zeta', 'position' => 1]);
    Book::factory()->for($this->user)->for($first)->create(['title' => 'Alfa', 'position' => 0]);

    $this->post(route('decorations.store'), ['kind' => 'cup', 'color' => 'rose'])
        ->assertRedirect()
        ->assertSessionHas('success', __('ui.decoration_added', [
            'name' => __('ui.decoration_cup'),
            'shelf' => 'Primera',
        ]));

    $decoration = $this->user->decorations()->sole();

    expect($decoration->shelf_id)->toBe($first->id)
        ->and($decoration->after_book_id)->toBe($last->id)
        ->and($decoration->sort)->toBe(0)
        ->and($decoration->kind)->toBe('cup')
        ->and($decoration->color)->toBe('rose');
});

it('apila varios objetos en el mismo hueco', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['position' => 0]);
    $book = Book::factory()->for($this->user)->for($shelf)->create(['position' => 0]);

    $this->post(route('decorations.store'), ['kind' => 'cup', 'color' => 'rose'])->assertRedirect();
    $this->post(route('decorations.store'), ['kind' => 'roses', 'color' => 'green'])->assertRedirect();

    expect($shelf->decorations()->pluck('sort')->all())->toBe([0, 1]);
});

it('rechaza un objeto o un color desconocido', function () {
    Shelf::factory()->for($this->user)->create();

    $this->post(route('decorations.store'), ['kind' => 'dragon', 'color' => 'rose'])
        ->assertSessionHasErrors('kind');

    $this->post(route('decorations.store'), ['kind' => 'cup', 'color' => 'neon'])
        ->assertSessionHasErrors('color');
});

it('mueve un objeto a otra estantería y lo ancla a un libro', function () {
    $from = Shelf::factory()->for($this->user)->create(['position' => 0]);
    $to = Shelf::factory()->for($this->user)->create(['name' => 'Destino', 'position' => 1]);
    $book = Book::factory()->for($this->user)->for($to)->create();
    $decoration = Decoration::factory()->for($this->user)->for($from)->create([
        'kind' => 'candle',
        'sort' => 0,
    ]);

    $this->patch(route('decorations.update', $decoration), [
        'shelf_id' => $to->id,
        'after_book_id' => $book->id,
        'sort' => 0,
    ])->assertRedirect()
        ->assertSessionHas('success', __('ui.decoration_moved', [
            'name' => __('ui.decoration_candle'),
            'shelf' => 'Destino',
        ]));

    expect($decoration->fresh()->shelf_id)->toBe($to->id)
        ->and($decoration->fresh()->after_book_id)->toBe($book->id)
        ->and($decoration->fresh()->sort)->toBe(0);
});

it('reordena los objetos del mismo hueco', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($shelf)->create();
    $a = Decoration::factory()->for($this->user)->for($shelf)->create(['after_book_id' => $book->id, 'sort' => 0]);
    $b = Decoration::factory()->for($this->user)->for($shelf)->create(['after_book_id' => $book->id, 'sort' => 1]);
    $c = Decoration::factory()->for($this->user)->for($shelf)->create(['after_book_id' => $book->id, 'sort' => 2]);

    $this->patch(route('decorations.update', $c), [
        'shelf_id' => $shelf->id,
        'after_book_id' => $book->id,
        'sort' => 0,
    ])->assertRedirect();

    expect($shelf->decorations()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('no permite mover un objeto ajeno', function () {
    $decoration = Decoration::factory()->for(User::factory())->create();
    $shelf = Shelf::factory()->for($this->user)->create();

    $this->patch(route('decorations.update', $decoration), [
        'shelf_id' => $shelf->id,
        'after_book_id' => null,
        'sort' => 0,
    ])->assertForbidden();
});

it('rechaza un ancla que no está en la estantería destino', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $other = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($other)->create();
    $decoration = Decoration::factory()->for($this->user)->for($shelf)->create();

    $this->patch(route('decorations.update', $decoration), [
        'shelf_id' => $shelf->id,
        'after_book_id' => $book->id,
        'sort' => 0,
    ])->assertStatus(422);
});

it('rechaza una estantería ajena', function () {
    $decoration = Decoration::factory()->for($this->user)->create();
    $shelf = Shelf::factory()->for(User::factory())->create();

    $this->patch(route('decorations.update', $decoration), [
        'shelf_id' => $shelf->id,
        'after_book_id' => null,
        'sort' => 0,
    ])->assertNotFound();
});

it('quita un objeto propio', function () {
    $decoration = Decoration::factory()->for($this->user)->create(['kind' => 'lamp']);

    $this->delete(route('decorations.destroy', $decoration))
        ->assertRedirect()
        ->assertSessionHas('success', __('ui.decoration_removed', [
            'name' => __('ui.decoration_lamp'),
        ]));

    expect(Decoration::find($decoration->id))->toBeNull();
});

it('no permite quitar un objeto ajeno', function () {
    $decoration = Decoration::factory()->for(User::factory())->create();

    $this->delete(route('decorations.destroy', $decoration))->assertForbidden();
});

it('responde con una pagina de inertia al quitar un objeto desde la biblioteca', function () {
    $decoration = Decoration::factory()->for($this->user)->create(['kind' => 'lamp']);

    $delete = $this->delete(route('decorations.destroy', $decoration), [], [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'Referer' => route('library.index'),
    ]);

    expect($delete->status())->toBe(303);

    $location = $delete->headers->get('Location');

    expect($location)->not->toBeNull();

    $follow = $this->get($location, [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia-Version' => app(InertiaMiddleware::class)->version(request()),
    ]);

    expect($follow->status())->toBe(200)
        ->and($follow->headers->get('X-Inertia'))->toBe('true')
        ->and($follow->headers->get('Content-Type'))->toContain('application/json');

    $page = json_decode($follow->getContent(), true);

    expect($page['component'])->toBe('Library/Index')
        ->and($page['props']['flash']['success'])->toBe(__('ui.decoration_removed', [
            'name' => __('ui.decoration_lamp'),
        ]))
        ->and($page['props']['decorations'])->toBe([]);

    expect(Decoration::find($decoration->id))->toBeNull();
});

it('quita todos los objetos del usuario', function () {
    $mine = Decoration::factory()->for($this->user)->create();
    $theirs = Decoration::factory()->for(User::factory())->create();

    $this->delete(route('decorations.clear'))
        ->assertRedirect()
        ->assertSessionHas('success', __('ui.decorations_cleared'));

    expect(Decoration::find($mine->id))->toBeNull()
        ->and(Decoration::find($theirs->id))->not->toBeNull();
});

it('no entrega las decoraciones en las estanterías', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['position' => 0]);
    Decoration::factory()->for($this->user)->for($shelf)->create(['kind' => 'cup']);

    $this->get(route('shelves.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('shelves.0.decorations'));
});

it('entrega solo las decoraciones propias en el módulo de decoraciones', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['name' => 'Mi estantería']);
    Decoration::factory()->for($this->user)->for($shelf)->create(['kind' => 'plant']);

    $other = User::factory()->create();
    $otherShelf = Shelf::factory()->for($other)->create();
    Decoration::factory()->for($other)->for($otherShelf)->create(['kind' => 'skull']);

    $this->get(route('decorations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Decorations/Index')
            ->has('decorations', 1)
            ->where('decorations.0.kind', 'plant')
            ->where('decorations.0.shelf.name', 'Mi estantería'));
});
