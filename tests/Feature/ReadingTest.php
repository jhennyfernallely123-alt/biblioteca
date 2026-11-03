<?php

use App\Models\Book;
use App\Models\ReadingSession;
use App\Models\Shelf;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('guarda una sesión de lectura y actualiza la página y el progreso', function () {
    $book = Book::factory()->for($this->user)->create([
        'total_pages' => 200,
        'current_page' => 20,
    ]);

    $this->postJson(route('reading.sessions.store', $book), [
        'minutes' => 45,
        'page' => 32,
    ])
        ->assertOk()
        ->assertJsonPath('book.current_page', 32)
        ->assertJsonPath('book.progress', 16)
        ->assertJsonPath('book.minutes', 45)
        ->assertJsonPath('book.status', 'reading');

    $book->refresh();

    expect($book->current_page)->toBe(32)
        ->and($this->user->readingSessions()->count())->toBe(1);

    $session = ReadingSession::firstOrFail();

    expect($session->book_id)->toBe($book->id)
        ->and($session->minutes)->toBe(45)
        ->and($session->page)->toBe(32)
        ->and($session->read_on->toDateString())->toBe(now()->toDateString());
});

it('no retrocede la página actual al registrar una página anterior', function () {
    $book = Book::factory()->for($this->user)->create([
        'total_pages' => 200,
        'current_page' => 50,
    ]);

    $this->postJson(route('reading.sessions.store', $book), [
        'minutes' => 20,
        'page' => 30,
    ])->assertOk();

    expect($book->fresh()->current_page)->toBe(50);
});

it('valida los minutos y la página de la sesión', function () {
    $book = Book::factory()->for($this->user)->create(['total_pages' => 100]);

    $this->postJson(route('reading.sessions.store', $book), [
        'minutes' => 0,
        'page' => -1,
    ])->assertStatus(422)->assertJsonValidationErrors(['minutes', 'page']);
});

it('actualiza las páginas totales y la página actual', function () {
    $book = Book::factory()->for($this->user)->create();

    $this->patchJson(route('reading.update', $book), [
        'total_pages' => 300,
        'current_page' => 120,
    ])
        ->assertOk()
        ->assertJsonPath('book.total_pages', 300)
        ->assertJsonPath('book.current_page', 120)
        ->assertJsonPath('book.progress', 40);
});

it('recorta la página actual a las páginas totales y marca el libro como terminado', function () {
    $book = Book::factory()->for($this->user)->create();

    $this->patchJson(route('reading.update', $book), [
        'total_pages' => 100,
        'current_page' => 150,
    ])
        ->assertOk()
        ->assertJsonPath('book.current_page', 100)
        ->assertJsonPath('book.progress', 100)
        ->assertJsonPath('book.status', 'finished');
});

it('devuelve el historial de sesiones de más reciente a más antigua', function () {
    $book = Book::factory()->for($this->user)->create(['total_pages' => 200]);

    ReadingSession::create([
        'user_id' => $this->user->id,
        'book_id' => $book->id,
        'read_on' => '2026-11-01',
        'minutes' => 30,
        'page' => 10,
    ]);
    ReadingSession::create([
        'user_id' => $this->user->id,
        'book_id' => $book->id,
        'read_on' => '2026-11-03',
        'minutes' => 60,
        'page' => 25,
    ]);

    $this->getJson(route('reading.show', $book))
        ->assertOk()
        ->assertJsonPath('sessions.0.read_on', '2026-11-03')
        ->assertJsonPath('sessions.1.read_on', '2026-11-01')
        ->assertJsonPath('book.minutes', 90);
});

it('elimina una sesión propia y recalcula el tiempo', function () {
    $book = Book::factory()->for($this->user)->create(['total_pages' => 200]);
    $session = ReadingSession::create([
        'user_id' => $this->user->id,
        'book_id' => $book->id,
        'read_on' => now()->toDateString(),
        'minutes' => 30,
        'page' => 10,
    ]);

    $this->deleteJson(route('reading.sessions.destroy', $session))
        ->assertOk()
        ->assertJsonPath('book.minutes', 0);

    expect(ReadingSession::find($session->id))->toBeNull();
});

it('no permite leer ni registrar sesiones en libros de otra persona', function () {
    $book = Book::factory()->for(User::factory())->create(['total_pages' => 100]);

    $this->getJson(route('reading.show', $book))->assertForbidden();
    $this->patchJson(route('reading.update', $book), [
        'total_pages' => 100,
        'current_page' => 10,
    ])->assertForbidden();
    $this->postJson(route('reading.sessions.store', $book), [
        'minutes' => 10,
    ])->assertForbidden();
});

it('no permite eliminar sesiones de otra persona', function () {
    $book = Book::factory()->for(User::factory())->create();
    $session = ReadingSession::create([
        'user_id' => $book->user_id,
        'book_id' => $book->id,
        'read_on' => now()->toDateString(),
        'minutes' => 30,
        'page' => 10,
    ]);

    $this->deleteJson(route('reading.sessions.destroy', $session))->assertForbidden();

    expect(ReadingSession::find($session->id))->not->toBeNull();
});

it('reúne en la estantería Leyendo solo los libros en curso', function () {
    $shelf = Shelf::factory()->for($this->user)->create();

    $reading = Book::factory()->for($this->user)->for($shelf)->create([
        'total_pages' => 100,
        'current_page' => 25,
    ]);
    Book::factory()->for($this->user)->for($shelf)->create([
        'total_pages' => 100,
        'current_page' => 100,
    ]);
    Book::factory()->for($this->user)->for($shelf)->create();

    $this->get(route('shelves.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shelves/Index')
            ->has('readingBooks', 1)
            ->where('readingBooks.0.id', $reading->id)
            ->where('readingBooks.0.progress', 25)
            ->where('readingBooks.0.reading_status', 'reading')
        );
});
