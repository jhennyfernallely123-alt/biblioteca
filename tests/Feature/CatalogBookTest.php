<?php

use App\Models\Book;
use App\Models\CatalogBook;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('relaciona la ficha del catálogo con su creadora y con las copias personales', function () {
    $master = User::factory()->master()->create();
    $catalogBook = CatalogBook::factory()->for($master, 'creator')->create();
    $book = Book::factory()->for($master)->create(['catalog_book_id' => $catalogBook->id]);

    expect($master->isMaster())->toBeTrue()
        ->and($master->catalogBooks()->count())->toBe(1)
        ->and($catalogBook->creator->is($master))->toBeTrue()
        ->and($catalogBook->books()->count())->toBe(1)
        ->and($book->catalogBook->is($catalogBook))->toBeTrue();
});

it('permite entrar al panel a la cuenta master', function () {
    $this->actingAs(User::factory()->master()->create())
        ->get(route('catalog.index'))
        ->assertOk();
});

it('prohibe el panel a una cuenta normal', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('catalog.index'))
        ->assertForbidden();
});

it('redirige a la invitada al login', function () {
    $this->get(route('catalog.index'))->assertRedirect(route('login'));
});

it('sirve la portada del catálogo solo a usuarias autenticadas', function () {
    Storage::fake('public');

    $book = CatalogBook::factory()->create(['cover_path' => 'covers/catalog.jpg']);
    Storage::disk('public')->put('covers/catalog.jpg', 'image');

    $this->get(route('catalog-covers.show', $book))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('catalog-covers.show', $book))
        ->assertOk();
});

it('devuelve 404 si la ficha no tiene portada', function () {
    Storage::fake('public');

    $book = CatalogBook::factory()->create(['cover_path' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('catalog-covers.show', $book))
        ->assertNotFound();
});

it('no permite activar is_master desde el perfil', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('settings.profile'), [
        'name' => $user->name,
        'nickname' => $user->nickname,
        'email' => $user->email,
        'is_master' => true,
    ])->assertRedirect();

    expect($user->fresh()->isMaster())->toBeFalse();
});

it('lista las fichas del catálogo', function () {
    CatalogBook::factory()->create(['title' => 'Visible']);

    $this->actingAs(User::factory()->master()->create())
        ->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Index')
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Visible'));
});

it('muestra las estanterías en el formulario de alta', function () {
    $master = User::factory()->master()->create();
    $shelf = Shelf::factory()->for($master)->create(['name' => 'Pendientes']);

    $this->actingAs($master)
        ->get(route('catalog.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Create')
            ->where('shelves.0.id', $shelf->id));
});

it('crea una ficha y una copia personal en la estantería elegida', function () {
    Storage::fake('public');
    Http::fake([
        'covers.test/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $master = User::factory()->master()->create();
    $shelf = Shelf::factory()->for($master)->create();

    $this->actingAs($master)->post(route('catalog.store'), [
        'title' => 'El libro perdido',
        'author' => 'Autora',
        'isbn' => '978-84-376-0494-7',
        'publisher' => 'Editorial',
        'published_year' => 2020,
        'shelf_id' => $shelf->id,
        'cover_url' => 'https://covers.test/portada.jpg',
    ])->assertRedirect(route('catalog.index'))
        ->assertSessionHas('success', __('ui.catalog_created'));

    $catalogBook = CatalogBook::firstOrFail();
    $book = Book::firstOrFail();

    expect($catalogBook->isbn)->toBe('9788437604947')
        ->and($catalogBook->cover_path)->not->toBeNull()
        ->and($book->title)->toBe('El libro perdido')
        ->and($book->shelf_id)->toBe($shelf->id)
        ->and($book->catalog_book_id)->toBe($catalogBook->id)
        ->and($book->cover_path)->not->toBe($catalogBook->cover_path);

    Storage::disk('public')->assertExists($catalogBook->cover_path);
    Storage::disk('public')->assertExists($book->cover_path);
});

it('no permite usar la estantería de otra usuaria', function () {
    $master = User::factory()->master()->create();
    $shelf = Shelf::factory()->for(User::factory())->create();

    $this->actingAs($master)->post(route('catalog.store'), [
        'title' => 'Intruso',
        'shelf_id' => $shelf->id,
    ])->assertForbidden();

    expect(CatalogBook::count())->toBe(0);
});

it('prohibe las demás rutas del panel a una cuenta normal', function () {
    $normal = User::factory()->create();
    $catalogBook = CatalogBook::factory()->create();

    $this->actingAs($normal)->get(route('catalog.create'))->assertForbidden();
    $this->actingAs($normal)->post(route('catalog.store'), [])->assertForbidden();
    $this->actingAs($normal)->get(route('catalog.edit', $catalogBook))->assertForbidden();
    $this->actingAs($normal)->patch(route('catalog.update', $catalogBook), [])->assertForbidden();
    $this->actingAs($normal)->delete(route('catalog.destroy', $catalogBook))->assertForbidden();
});

it('normaliza el isbn y rechaza duplicados', function () {
    $master = User::factory()->master()->create();
    $shelf = Shelf::factory()->for($master)->create();

    $this->actingAs($master)->post(route('catalog.store'), [
        'title' => 'Rayuela',
        'shelf_id' => $shelf->id,
        'isbn' => '978-84-376-0494-7',
    ])->assertRedirect();

    expect(CatalogBook::firstOrFail()->isbn)->toBe('9788437604947');

    $this->actingAs($master)->post(route('catalog.store'), [
        'title' => 'Rayuela repetida',
        'shelf_id' => $shelf->id,
        'isbn' => '9788437604947',
    ])->assertSessionHasErrors('isbn');

    expect(CatalogBook::count())->toBe(1);
});

it('rechaza un isbn inválido', function () {
    $master = User::factory()->master()->create();
    $shelf = Shelf::factory()->for($master)->create();

    $this->actingAs($master)->post(route('catalog.store'), [
        'title' => 'Inválido',
        'shelf_id' => $shelf->id,
        'isbn' => '123',
    ])->assertSessionHasErrors('isbn');

    expect(CatalogBook::count())->toBe(0);
});

it('no crea la ficha si la portada remota falla', function () {
    Http::fake(['covers.test/*' => Http::response('', 500)]);

    $master = User::factory()->master()->create();
    $shelf = Shelf::factory()->for($master)->create();

    $this->actingAs($master)->post(route('catalog.store'), [
        'title' => 'Sin portada',
        'shelf_id' => $shelf->id,
        'cover_url' => 'https://covers.test/rota.jpg',
    ])->assertSessionHasErrors('cover_url');

    expect(CatalogBook::count())->toBe(0)
        ->and(Book::count())->toBe(0);
});

it('edita la ficha sin tocar las copias personales', function () {
    $master = User::factory()->master()->create();
    $catalogBook = CatalogBook::factory()->for($master, 'creator')->create(['title' => 'Antes']);
    $book = Book::factory()->for($master)->create([
        'title' => 'Antes',
        'catalog_book_id' => $catalogBook->id,
    ]);

    $this->actingAs($master)->patch(route('catalog.update', $catalogBook), [
        'title' => 'Después',
    ])->assertRedirect(route('catalog.index'));

    expect($catalogBook->fresh()->title)->toBe('Después')
        ->and($book->fresh()->title)->toBe('Antes');
});

it('reemplaza la portada de la ficha y borra la anterior', function () {
    Storage::fake('public');
    Storage::disk('public')->put('covers/old.jpg', 'old');

    $master = User::factory()->master()->create();
    $catalogBook = CatalogBook::factory()->for($master, 'creator')->create([
        'cover_path' => 'covers/old.jpg',
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'cover.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
    );

    $this->actingAs($master)->patch(route('catalog.update', $catalogBook), [
        'title' => $catalogBook->title,
        'cover' => $file,
    ])->assertRedirect(route('catalog.index'));

    $catalogBook->refresh();

    expect($catalogBook->cover_path)->not->toBe('covers/old.jpg');

    Storage::disk('public')->assertMissing('covers/old.jpg');
    Storage::disk('public')->assertExists($catalogBook->cover_path);
});

it('conserva la portada anterior si la nueva URL falla', function () {
    Storage::fake('public');
    Storage::disk('public')->put('covers/old.jpg', 'old');
    Http::fake(['covers.test/*' => Http::response('', 500)]);

    $master = User::factory()->master()->create();
    $catalogBook = CatalogBook::factory()->for($master, 'creator')->create([
        'cover_path' => 'covers/old.jpg',
    ]);

    $this->actingAs($master)->patch(route('catalog.update', $catalogBook), [
        'title' => $catalogBook->title,
        'cover_url' => 'https://covers.test/rota.jpg',
    ])->assertSessionHasErrors('cover_url');

    expect($catalogBook->fresh()->cover_path)->toBe('covers/old.jpg');
});

it('elimina la ficha y conserva la copia personal', function () {
    Storage::fake('public');
    Storage::disk('public')->put('covers/catalog.jpg', 'catalog');
    Storage::disk('public')->put('covers/personal.jpg', 'personal');

    $master = User::factory()->master()->create();
    $catalogBook = CatalogBook::factory()->for($master, 'creator')->create([
        'cover_path' => 'covers/catalog.jpg',
    ]);
    $book = Book::factory()->for($master)->create([
        'catalog_book_id' => $catalogBook->id,
        'cover_path' => 'covers/personal.jpg',
    ]);

    $this->actingAs($master)->delete(route('catalog.destroy', $catalogBook))
        ->assertRedirect(route('catalog.index'));

    expect(CatalogBook::find($catalogBook->id))->toBeNull()
        ->and($book->fresh())->not->toBeNull()
        ->and($book->fresh()->catalog_book_id)->toBeNull();

    Storage::disk('public')->assertMissing('covers/catalog.jpg');
    Storage::disk('public')->assertExists('covers/personal.jpg');
});
