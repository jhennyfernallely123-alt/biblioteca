# Catálogo master — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir que la cuenta master mantenga un catálogo global de libros ausentes y que cualquier usuaria autenticada los encuentre en la búsqueda antes que los resultados de las APIs.

**Architecture:** Tabla `catalog_books` independiente de `books`, con vínculo opcional `books.catalog_book_id`. El panel master crea la ficha y una copia personal en la estantería elegida. `BookLookupService` consulta primero el catálogo local (sin caché) y después las APIs (con la caché actual), sin deduplicar. Las portadas se sirven por ruta autenticada y se copian al libro personal para que sean archivos independientes.

**Tech Stack:** Laravel 13, PHP 8.3, Pest, Inertia.js, Vue 3, Tailwind CSS 4, MySQL/SQLite, Vitest.

---

## Reglas de ejecución

- **La usuaria hace los commits.** No ejecutar `git add`, `git commit`, `git push`, `git tag` ni ninguna operación que modifique el historial. Al final de cada tarea, detenerse e informar de los archivos tocados para que la usuaria revise y commitee.
- No añadir colores, tipografías, sombras ni radios nuevos. Usar los tokens de `resources/css/app.css` y los componentes existentes.
- Todo texto visible nuevo va en `lang/es.json` y `lang/en.json`; no escribir literales en componentes Vue.
- Mantener compatibilidad con InfinityFree: sin Node.js en el servidor, sin `storage:link`, sin colas ni workers.
- Código y nombres técnicos en inglés; textos de interfaz en español e inglés.
- No usar `rg` en PowerShell 5.1. Para leer archivos, usar las herramientas de lectura del agente.

> **Nota de ejecución (2026-11-12):** el test de Inertia exige que el componente Vue exista en disco y el backend ya usaba las claves `ui.catalog_*`. Por eso las páginas Vue (Task 8) y las claves i18n (Task 9, pasos 1-2) se crearon durante la Task 4. La Task 8 queda como verificación de build y la Task 9 como enlaces de navegación.

## Mapa de archivos

**Crear**

- `database/migrations/2026_11_12_000001_add_is_master_to_users_table.php`
- `database/migrations/2026_11_12_000002_create_catalog_books_table.php`
- `database/migrations/2026_11_12_000003_add_catalog_book_id_to_books_table.php`
- `database/factories/CatalogBookFactory.php`
- `app/Models/CatalogBook.php`
- `app/Policies/CatalogBookPolicy.php`
- `app/Http/Middleware/EnsureMaster.php`
- `app/Http/Controllers/CatalogBookController.php`
- `app/Http/Controllers/CatalogCoverController.php`
- `app/Http/Requests/StoreCatalogBookRequest.php`
- `app/Http/Requests/UpdateCatalogBookRequest.php`
- `app/Services/BookPlacementService.php`
- `app/Services/ShelfDisplayService.php`
- `resources/js/bookLookup.js`
- `resources/js/bookLookup.test.js`
- `resources/js/Components/CatalogBookForm.vue`
- `resources/js/Pages/Catalog/Index.vue`
- `resources/js/Pages/Catalog/Create.vue`
- `resources/js/Pages/Catalog/Edit.vue`
- `tests/Feature/CatalogBookTest.php`

**Modificar**

- `app/Models/User.php`
- `app/Models/Book.php`
- `database/factories/UserFactory.php`
- `bootstrap/app.php`
- `routes/web.php`
- `app/Http/Controllers/BookController.php`
- `app/Http/Requests/StoreBookRequest.php`
- `app/Services/CoverService.php`
- `app/Services/BookLookupService.php`
- `resources/js/Pages/Books/Create.vue`
- `resources/js/Components/AppShell.vue`
- `resources/js/Components/BottomNav.vue`
- `lang/es.json`
- `lang/en.json`
- `tests/Feature/BookLookupServiceTest.php`
- `tests/Feature/BookTest.php`
- `docs/DISENO.md`
- `docs/superpowers/specs/2026-11-12-catalogo-master-design.md`

---

### Task 1: Base de datos, modelos y factory

**Files:**
- Create: `database/migrations/2026_11_12_000001_add_is_master_to_users_table.php`
- Create: `database/migrations/2026_11_12_000002_create_catalog_books_table.php`
- Create: `database/migrations/2026_11_12_000003_add_catalog_book_id_to_books_table.php`
- Create: `app/Models/CatalogBook.php`
- Create: `database/factories/CatalogBookFactory.php`
- Create: `tests/Feature/CatalogBookTest.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Book.php`
- Modify: `database/factories/UserFactory.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CatalogBookTest.php`:

```php
<?php

use App\Models\Book;
use App\Models\CatalogBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
php artisan test --filter=CatalogBookTest
```

Expected: FAIL because `App\Models\CatalogBook` does not exist.

- [ ] **Step 3: Create the migrations**

`database/migrations/2026_11_12_000001_add_is_master_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_master')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_master');
        });
    }
};
```

`database/migrations/2026_11_12_000002_create_catalog_books_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('isbn', 20)->nullable()->unique();
            $table->string('publisher', 150)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_books');
    }
};
```

`database/migrations/2026_11_12_000003_add_catalog_book_id_to_books_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->foreignId('catalog_book_id')
                ->nullable()
                ->constrained('catalog_books')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catalog_book_id');
        });
    }
};
```

- [ ] **Step 4: Create the model and factory**

`app/Models/CatalogBook.php`:

```php
<?php

namespace App\Models;

use Database\Factories\CatalogBookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogBook extends Model
{
    /** @use HasFactory<CatalogBookFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by', 'title', 'author', 'isbn', 'publisher', 'published_year', 'cover_path',
    ];

    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
```

`database/factories/CatalogBookFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\CatalogBook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogBook>
 */
class CatalogBookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title' => ucfirst(fake()->sentence(3)),
            'author' => fake()->name(),
            'isbn' => null,
            'publisher' => fake()->company(),
            'published_year' => fake()->numberBetween(1900, 2026),
            'cover_path' => null,
        ];
    }
}
```

- [ ] **Step 5: Update `User`, `Book` and `UserFactory`**

In `app/Models/User.php`:

- Do **not** add `is_master` to the `#[Fillable]` attribute.
- Add this cast inside `casts()`:

```php
'is_master' => 'boolean',
```

- Add these methods next to the other relationships:

```php
public function isMaster(): bool
{
    return (bool) $this->is_master;
}

public function catalogBooks(): HasMany
{
    return $this->hasMany(CatalogBook::class, 'created_by');
}
```

In `app/Models/Book.php`:

- Add `'catalog_book_id'` to the `$fillable` array.
- Add this relationship next to `shelf()`:

```php
public function catalogBook(): BelongsTo
{
    return $this->belongsTo(CatalogBook::class);
}
```

In `database/factories/UserFactory.php`, add this state after `unverified()`:

```php
public function master(): static
{
    return $this->afterCreating(function (User $user) {
        $user->forceFill(['is_master' => true])->save();
    });
}
```

- [ ] **Step 6: Run tests and verify they pass**

Run:

```bash
php artisan test --filter=CatalogBookTest
```

Expected: PASS, 1 test.

Run the existing book tests to confirm the new nullable column did not break anything:

```bash
php artisan test --filter=BookTest
```

Expected: PASS.

- [ ] **Step 7: Pause for review**

Inform the user of the files created/modified. The user reviews and commits.

---

### Task 2: Permisos, rutas y portada autenticada

**Files:**
- Create: `app/Policies/CatalogBookPolicy.php`
- Create: `app/Http/Middleware/EnsureMaster.php`
- Create: `app/Http/Controllers/CatalogBookController.php`
- Create: `app/Http/Controllers/CatalogCoverController.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/CatalogBookTest.php`

- [ ] **Step 1: Add the failing tests**

At the top of `tests/Feature/CatalogBookTest.php`, add:

```php
use Illuminate\Support\Facades\Storage;
```

Append these tests:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test --filter=CatalogBookTest
```

Expected: FAIL because `catalog.index` and `catalog-covers.show` are not defined.

- [ ] **Step 3: Create the policy and middleware**

`app/Policies/CatalogBookPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\CatalogBook;
use App\Models\User;

class CatalogBookPolicy
{
    public function manage(User $user, ?CatalogBook $catalogBook = null): bool
    {
        return $user->isMaster();
    }
}
```

`app/Http/Middleware/EnsureMaster.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMaster
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isMaster(), 403);

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the middleware alias**

In `bootstrap/app.php`:

- Add `use App\Http\Middleware\EnsureMaster;` with the other middleware imports.
- Add the alias:

```php
$middleware->alias([
    'nickname' => EnsureNicknameIsSet::class,
    'master' => EnsureMaster::class,
]);
```

- [ ] **Step 5: Add the routes and minimal controller**

`app/Http/Controllers/CatalogCoverController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\CatalogBook;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogCoverController extends Controller
{
    public function __invoke(CatalogBook $catalogBook): StreamedResponse
    {
        abort_unless($catalogBook->cover_path, 404);
        abort_unless(Storage::disk('public')->exists($catalogBook->cover_path), 404);

        return Storage::disk('public')->response($catalogBook->cover_path);
    }
}
```

`app/Http/Controllers/CatalogBookController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\CatalogBook;
use Inertia\Inertia;
use Inertia\Response;

class CatalogBookController extends Controller
{
    public function index(): Response
    {
        $this->authorize('manage', CatalogBook::class);

        return Inertia::render('Catalog/Index', [
            'books' => [],
        ]);
    }
}
```

In `routes/web.php`:

- Add imports:

```php
use App\Http\Controllers\CatalogBookController;
use App\Http\Controllers\CatalogCoverController;
```

- Add the authenticated cover route near the other cover routes:

```php
Route::get('/catalog-covers/{catalogBook}', CatalogCoverController::class)
    ->middleware('auth')
    ->name('catalog-covers.show');
```

- Add the master route group before `require __DIR__.'/auth.php';`:

```php
Route::middleware(['auth', 'nickname', 'master'])
    ->prefix('catalog')
    ->name('catalog.')
    ->group(function () {
        Route::get('/', [CatalogBookController::class, 'index'])->name('index');
    });
```

- [ ] **Step 6: Run tests and verify they pass**

Run:

```bash
php artisan test --filter=CatalogBookTest
```

Expected: PASS, 7 tests.

- [ ] **Step 7: Pause for review**

Inform the user of the files created/modified. The user reviews and commits.

---

### Task 3: Extraer servicios de colocación y estanterías

**Files:**
- Create: `app/Services/BookPlacementService.php`
- Create: `app/Services/ShelfDisplayService.php`
- Modify: `app/Http/Controllers/BookController.php`
- Modify: `app/Services/CoverService.php`

Este task es un refactor sin cambio de comportamiento. Las pruebas existentes de `BookTest` son la red de seguridad.

- [ ] **Step 1: Run the baseline tests**

Run:

```bash
php artisan test --filter=BookTest
```

Expected: PASS. If anything fails, stop and report it before changing code.

- [ ] **Step 2: Create `BookPlacementService`**

`app/Services/BookPlacementService.php`:

```php
<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;

class BookPlacementService
{
    public function nextPosition(int $shelfId): int
    {
        $max = Book::where('shelf_id', $shelfId)->max('position');

        return $max === null ? 0 : (int) $max + 1;
    }

    public function nextBand(User $user): array
    {
        $last = $user->books()->orderByDesc('band')->first(['band']);
        $band = $last?->band ?? 0;
        $count = $user->books()->where('band', $band)->count();

        if ($count >= Book::BOOKS_PER_BAND) {
            return ['band' => $band + 1, 'position' => 0];
        }

        return ['band' => $band, 'position' => $count];
    }
}
```

- [ ] **Step 3: Create `ShelfDisplayService`**

`app/Services/ShelfDisplayService.php`:

```php
<?php

namespace App\Services;

use App\Models\User;

class ShelfDisplayService
{
    public function __construct(
        private TranslationService $translator,
    ) {}

    public function forUser(User $user): array
    {
        return $user->shelves()
            ->orderBy('position')
            ->get(['id', 'name'])
            ->map(function ($shelf) {
                $shelf->display_name = app()->getLocale() === 'es'
                    ? $shelf->name
                    : ($this->translator->translateText($shelf->name, app()->getLocale(), 'es') ?? $shelf->name);

                return $shelf;
            })
            ->all();
    }
}
```

- [ ] **Step 4: Add `CoverService::copy`**

In `app/Services/CoverService.php`, add this method after `storeFromUrl()`:

```php
public function copy(?string $path): ?string
{
    if (! $path || ! Storage::disk('public')->exists($path)) {
        return null;
    }

    $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
    $destination = 'covers/'.Str::uuid().'.'.$extension;

    if (! Storage::disk('public')->copy($path, $destination)) {
        return null;
    }

    return $destination;
}
```

- [ ] **Step 5: Refactor `BookController`**

In `app/Http/Controllers/BookController.php`:

- Remove `use App\Services\TranslationService;`.
- Add:

```php
use App\Services\BookPlacementService;
use App\Services\ShelfDisplayService;
```

- Replace the constructor with:

```php
public function __construct(
    private CoverService $covers,
    private BookPlacementService $placement,
    private ShelfDisplayService $shelves,
) {}
```

- In `create()`, replace the value `$this->shelvesFor($request)` with `$this->shelves->forUser($request->user())` (keep the existing `'shelves' =>` key).

- In `store()`, replace the position and band lines with:

```php
$data['position'] = $this->placement->nextPosition((int) $data['shelf_id']);
$data['view_mode'] = $request->user()->default_view_mode ?? 'covers';
$nextBand = $this->placement->nextBand($request->user());
$data['band'] = $nextBand['band'];
$data['band_position'] = $nextBand['position'];
```

- In `update()`, replace `$this->nextPosition(...)` with `$this->placement->nextPosition(...)`.
- In `move()`, replace `$this->nextPosition($targetShelfId)` inside the transaction with `$this->placement->nextPosition($targetShelfId)`.
- Delete the private methods `nextPosition()`, `nextBand()` and `shelvesFor()`.
- Keep `resequenceShelf()`, `placeAt()` and `ensureShelfOwnership()` unchanged.

- [ ] **Step 6: Run tests and verify they still pass**

Run:

```bash
php artisan test --filter=BookTest
```

Expected: PASS, same tests as the baseline.

Run the shelf translation test as well:

```bash
php artisan test --filter=ShelfTranslationTest
```

Expected: PASS.

- [ ] **Step 7: Pause for review**

Inform the user of the files created/modified. The user reviews and commits.

---

### Task 4: CRUD del catálogo, formularios y copia personal

**Files:**
- Create: `app/Http/Requests/StoreCatalogBookRequest.php`
- Create: `app/Http/Requests/UpdateCatalogBookRequest.php`
- Modify: `app/Http/Controllers/CatalogBookController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/CatalogBookTest.php`

- [ ] **Step 1: Add the failing tests**

At the top of `tests/Feature/CatalogBookTest.php`, replace the imports with:

```php
use App\Models\Book;
use App\Models\CatalogBook;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
```

Append these tests:

```php
it('lista las fichas del catálogo', function () {
    CatalogBook::factory()->create(['title' => 'Visible']);

    $this->actingAs(User::factory()->master()->create())
        ->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Index')
            ->has('books', 1)
            ->where('books.0.title', 'Visible'));
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test --filter=CatalogBookTest
```

Expected: FAIL because `catalog.store`, `catalog.create`, `catalog.update` and `catalog.destroy` are not defined.

- [ ] **Step 3: Create the form requests**

`app/Http/Requests/StoreCatalogBookRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Services\Isbn;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCatalogBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('isbn')) {
            return;
        }

        $isbn = $this->input('isbn');

        $this->merge([
            'isbn' => filled($isbn) ? Isbn::normalize((string) $isbn) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && ! Isbn::isValid((string) $value)) {
                        $fail(__('ui.catalog_isbn_invalid'));
                    }
                },
                Rule::unique('catalog_books', 'isbn'),
            ],
            'publisher' => ['nullable', 'string', 'max:150'],
            'published_year' => ['nullable', 'integer', 'min:0', 'max:2100'],
            'shelf_id' => ['required', 'integer', 'exists:shelves,id'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
```

`app/Http/Requests/UpdateCatalogBookRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Services\Isbn;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatalogBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('isbn')) {
            return;
        }

        $isbn = $this->input('isbn');

        $this->merge([
            'isbn' => filled($isbn) ? Isbn::normalize((string) $isbn) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && ! Isbn::isValid((string) $value)) {
                        $fail(__('ui.catalog_isbn_invalid'));
                    }
                },
                Rule::unique('catalog_books', 'isbn')->ignore($this->route('catalogBook')),
            ],
            'publisher' => ['nullable', 'string', 'max:150'],
            'published_year' => ['nullable', 'integer', 'min:0', 'max:2100'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
```

- [ ] **Step 4: Replace `CatalogBookController` with the full implementation**

`app/Http/Controllers/CatalogBookController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogBookRequest;
use App\Http\Requests\UpdateCatalogBookRequest;
use App\Models\CatalogBook;
use App\Services\BookPlacementService;
use App\Services\CoverService;
use App\Services\ShelfDisplayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CatalogBookController extends Controller
{
    public function __construct(
        private CoverService $covers,
        private BookPlacementService $placement,
        private ShelfDisplayService $shelves,
    ) {}

    public function index(): Response
    {
        $this->authorize('manage', CatalogBook::class);

        return Inertia::render('Catalog/Index', [
            'books' => CatalogBook::query()
                ->orderBy('title')
                ->get()
                ->map(fn (CatalogBook $book) => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'publisher' => $book->publisher,
                    'published_year' => $book->published_year,
                    'cover_url' => $book->cover_path
                        ? route('catalog-covers.show', $book, false)
                        : null,
                ])
                ->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manage', CatalogBook::class);

        return Inertia::render('Catalog/Create', [
            'shelves' => $this->shelves->forUser($request->user()),
        ]);
    }

    public function store(StoreCatalogBookRequest $request): RedirectResponse
    {
        $this->authorize('manage', CatalogBook::class);

        $data = $request->validated();
        $shelfId = (int) $data['shelf_id'];

        $this->ensureShelfOwnership($request, $shelfId);

        $coverPath = null;
        $bookCoverPath = null;

        try {
            if ($request->hasFile('cover')) {
                $coverPath = $this->covers->storeUploaded($request->file('cover'));
            } elseif ($request->filled('cover_url')) {
                $coverPath = $this->covers->storeFromUrl($request->input('cover_url'));

                if ($coverPath === null) {
                    throw ValidationException::withMessages([
                        'cover_url' => __('ui.catalog_cover_error'),
                    ]);
                }
            }

            DB::transaction(function () use ($request, $data, $shelfId, $coverPath, &$bookCoverPath) {
                $catalogBook = CatalogBook::create([
                    'created_by' => $request->user()->id,
                    'title' => $data['title'],
                    'author' => $data['author'] ?? null,
                    'isbn' => $data['isbn'] ?? null,
                    'publisher' => $data['publisher'] ?? null,
                    'published_year' => $data['published_year'] ?? null,
                    'cover_path' => $coverPath,
                ]);

                $bookCoverPath = $catalogBook->cover_path
                    ? $this->covers->copy($catalogBook->cover_path)
                    : null;

                $nextBand = $this->placement->nextBand($request->user());

                $request->user()->books()->create([
                    'shelf_id' => $shelfId,
                    'position' => $this->placement->nextPosition($shelfId),
                    'band' => $nextBand['band'],
                    'band_position' => $nextBand['position'],
                    'view_mode' => $request->user()->default_view_mode ?? 'covers',
                    'title' => $catalogBook->title,
                    'author' => $catalogBook->author,
                    'isbn' => $catalogBook->isbn,
                    'publisher' => $catalogBook->publisher,
                    'published_year' => $catalogBook->published_year,
                    'cover_path' => $bookCoverPath,
                    'catalog_book_id' => $catalogBook->id,
                ]);
            });
        } catch (\Throwable $e) {
            $this->covers->delete($coverPath);
            $this->covers->delete($bookCoverPath);

            throw $e;
        }

        return redirect()->route('catalog.index')
            ->with('success', __('ui.catalog_created'));
    }

    public function edit(CatalogBook $catalogBook): Response
    {
        $this->authorize('manage', CatalogBook::class);

        return Inertia::render('Catalog/Edit', [
            'book' => [
                'id' => $catalogBook->id,
                'title' => $catalogBook->title,
                'author' => $catalogBook->author,
                'isbn' => $catalogBook->isbn,
                'publisher' => $catalogBook->publisher,
                'published_year' => $catalogBook->published_year,
                'cover_url' => $catalogBook->cover_path
                    ? route('catalog-covers.show', $catalogBook, false)
                    : null,
            ],
        ]);
    }

    public function update(UpdateCatalogBookRequest $request, CatalogBook $catalogBook): RedirectResponse
    {
        $this->authorize('manage', CatalogBook::class);

        $data = $request->validated();
        unset($data['cover'], $data['cover_url']);

        $oldCover = $catalogBook->cover_path;
        $newCover = null;

        try {
            if ($request->hasFile('cover')) {
                $newCover = $this->covers->storeUploaded($request->file('cover'));
            } elseif ($request->filled('cover_url')) {
                $newCover = $this->covers->storeFromUrl($request->input('cover_url'));

                if ($newCover === null) {
                    throw ValidationException::withMessages([
                        'cover_url' => __('ui.catalog_cover_error'),
                    ]);
                }
            }

            if ($newCover !== null) {
                $data['cover_path'] = $newCover;
            }

            $catalogBook->update($data);

            if ($oldCover && $newCover !== null && $newCover !== $oldCover) {
                $this->covers->delete($oldCover);
            }
        } catch (\Throwable $e) {
            $this->covers->delete($newCover);

            throw $e;
        }

        return redirect()->route('catalog.index')
            ->with('success', __('ui.catalog_updated'));
    }

    public function destroy(CatalogBook $catalogBook): RedirectResponse
    {
        $this->authorize('manage', CatalogBook::class);

        $cover = $catalogBook->cover_path;
        $catalogBook->delete();
        $this->covers->delete($cover);

        return redirect()->route('catalog.index')
            ->with('success', __('ui.catalog_deleted'));
    }

    private function ensureShelfOwnership(Request $request, int $shelfId): void
    {
        abort_unless(
            $request->user()->shelves()->whereKey($shelfId)->exists(),
            403
        );
    }
}
```

- [ ] **Step 5: Add the remaining catalog routes**

In the `catalog` route group in `routes/web.php`, add:

```php
Route::get('/create', [CatalogBookController::class, 'create'])->name('create');
Route::post('/', [CatalogBookController::class, 'store'])->name('store');
Route::get('/{catalogBook}/edit', [CatalogBookController::class, 'edit'])->name('edit');
Route::patch('/{catalogBook}', [CatalogBookController::class, 'update'])->name('update');
Route::delete('/{catalogBook}', [CatalogBookController::class, 'destroy'])->name('destroy');
```

- [ ] **Step 6: Run tests and verify they pass**

Run:

```bash
php artisan test --filter=CatalogBookTest
```

Expected: PASS, 19 tests.

Run the affected existing tests:

```bash
php artisan test --filter=BookTest
php artisan test --filter=ShelfTranslationTest
```

Expected: PASS.

- [ ] **Step 7: Pause for review**

Inform the user of the files created/modified. The user reviews and commits.

---

### Task 5: Búsqueda local antes que las APIs

**Files:**
- Modify: `app/Services/BookLookupService.php`
- Modify: `tests/Feature/BookLookupServiceTest.php`

- [ ] **Step 1: Add the failing tests**

At the top of `tests/Feature/BookLookupServiceTest.php`, add:

```php
use App\Models\CatalogBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
```

Immediately after the imports, add:

```php
uses(RefreshDatabase::class);
```

Append these tests:

```php
it('devuelve las fichas locales antes que los resultados de la api', function () {
    $local = CatalogBook::factory()->create([
        'title' => 'Rayuela local',
        'isbn' => '9788437604947',
    ]);

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [['volumeInfo' => ['title' => 'Rayuela API', 'language' => 'es']]],
        ]),
    ]);

    $results = app(BookLookupService::class)->search('rayuela');

    expect($results[0]['title'])->toBe('Rayuela local')
        ->and($results[0]['catalog_book_id'])->toBe($local->id)
        ->and($results[1]['title'])->toBe('Rayuela API');
});

it('devuelve fichas locales aunque las apis fallen', function () {
    CatalogBook::factory()->create(['title' => 'Rayuela local']);

    Http::fake(fn () => throw new ConnectionException('fallo'));

    $results = app(BookLookupService::class)->search('rayuela');

    expect($results)->toHaveCount(1)
        ->and($results[0]['title'])->toBe('Rayuela local');
});

it('encuentra fichas locales por isbn', function () {
    $local = CatalogBook::factory()->create([
        'title' => 'Rayuela local',
        'isbn' => '9788437604947',
    ]);

    Http::fake([
        'www.googleapis.com/*' => Http::response(['items' => []]),
        'openlibrary.org/*' => Http::response(['docs' => []]),
    ]);

    $results = app(BookLookupService::class)->byIsbn('978-84-376-0494-7');

    expect($results[0]['catalog_book_id'])->toBe($local->id)
        ->and($results[0]['title'])->toBe('Rayuela local');
});

it('no traduce las fichas locales', function () {
    CatalogBook::factory()->create(['title' => 'The Local Book']);

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The API Book',
                    'language' => 'en',
                ],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'El libro API'],
            'responseStatus' => 200,
        ]),
    ]);

    $results = app(BookLookupService::class)->search('book');

    expect($results[0]['title'])->toBe('The Local Book')
        ->and($results[1]['title'])->toBe('El libro API');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test --filter=BookLookupServiceTest
```

Expected: FAIL because `BookLookupService` does not query `catalog_books`.

- [ ] **Step 3: Replace `BookLookupService`**

`app/Services/BookLookupService.php`:

```php
<?php

namespace App\Services;

use App\Models\CatalogBook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BookLookupService
{
    public function __construct(
        private GoogleBooksService $google,
        private OpenLibraryService $openLibrary,
        private TranslationService $translator,
    ) {}

    public function byIsbn(string $value): array
    {
        $isbn = Isbn::normalize($value);

        if (! Isbn::isValid($isbn)) {
            throw ValidationException::withMessages([
                'isbn' => 'El ISBN no es válido.',
            ]);
        }

        $local = $this->localByIsbn($isbn);

        $results = Cache::remember(
            "lookup:v5:isbn:{$isbn}",
            now()->addDay(),
            fn () => $this->google->byIsbn($isbn) ?: $this->openLibrary->byIsbn($isbn)
        );

        return [
            ...$local,
            ...$this->translator->translateResults($results, app()->getLocale()),
        ];
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return [];
        }

        $local = $this->localSearch($query);

        $results = Cache::remember(
            'lookup:v6:q:'.md5($query),
            now()->addDay(),
            fn () => $this->google->search($query) ?: $this->openLibrary->search($query)
        );

        $locale = app()->getLocale();

        return [
            ...$local,
            ...$this->translator->translateResults(
                $this->withOfficialTitles($results, $locale),
                $locale
            ),
        ];
    }

    private function localByIsbn(string $isbn): array
    {
        return CatalogBook::query()
            ->where('isbn', $isbn)
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn (CatalogBook $book) => $this->formatLocal($book))
            ->all();
    }

    private function localSearch(string $query): array
    {
        $term = '%'.$this->escapeLike($query).'%';

        $builder = CatalogBook::query()
            ->where(function ($builder) use ($term) {
                $builder->where('title', 'like', $term)
                    ->orWhere('author', 'like', $term);
            });

        if (Isbn::isValid($query)) {
            $builder->orWhere('isbn', Isbn::normalize($query));
        }

        return $builder
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn (CatalogBook $book) => $this->formatLocal($book))
            ->all();
    }

    private function formatLocal(CatalogBook $book): array
    {
        return [
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'publisher' => $book->publisher,
            'published_year' => $book->published_year,
            'cover_url' => $book->cover_path
                ? route('catalog-covers.show', $book, false)
                : null,
            'catalog_book_id' => $book->id,
        ];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    private function withOfficialTitles(array $results, string $locale): array
    {
        if ($locale !== 'es') {
            return $results;
        }

        $candidates = [];

        foreach ($results as $index => $result) {
            if (($result['work_key'] ?? null) === null) {
                continue;
            }

            $candidates[$index] = $result;
        }

        foreach ($this->openLibrary->spanishTitles($candidates) as $index => $title) {
            $current = $results[$index]['title'];

            if (mb_strtolower($title) !== mb_strtolower($current)) {
                $results[$index]['original_title'] = $current;
            }

            $results[$index]['title'] = $title;
            $results[$index]['language'] = 'es';
        }

        return $results;
    }
}
```

- [ ] **Step 4: Run tests and verify they pass**

Run:

```bash
php artisan test --filter=BookLookupServiceTest
```

Expected: PASS.

Run the endpoint test as well:

```bash
php artisan test --filter=BookLookupEndpointTest
```

Expected: PASS.

- [ ] **Step 5: Pause for review**

Inform the user of the files modified. The user reviews and commits.

---

### Task 6: Copiar la portada al añadir un libro personal

**Files:**
- Modify: `app/Http/Requests/StoreBookRequest.php`
- Modify: `app/Http/Controllers/BookController.php`
- Modify: `tests/Feature/BookTest.php`

- [ ] **Step 1: Add the failing tests**

At the top of `tests/Feature/BookTest.php`, add:

```php
use App\Models\CatalogBook;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
```

Append these tests:

```php
it('copia la portada del catálogo al guardar un libro', function () {
    Storage::fake('public');

    $shelf = Shelf::factory()->for($this->user)->create();
    $catalogBook = CatalogBook::factory()->create(['cover_path' => 'covers/catalog.jpg']);
    Storage::disk('public')->put('covers/catalog.jpg', 'catalog-image');

    $this->post(route('books.store'), [
        'title' => 'Desde el catálogo',
        'shelf_id' => $shelf->id,
        'catalog_book_id' => $catalogBook->id,
    ])->assertRedirect();

    $book = Book::where('title', 'Desde el catálogo')->firstOrFail();

    expect($book->catalog_book_id)->toBe($catalogBook->id)
        ->and($book->cover_path)->not->toBeNull()
        ->and($book->cover_path)->not->toBe('covers/catalog.jpg');

    Storage::disk('public')->assertExists($book->cover_path);
});

it('da prioridad al archivo subido sobre la portada del catálogo', function () {
    Storage::fake('public');

    $shelf = Shelf::factory()->for($this->user)->create();
    $catalogBook = CatalogBook::factory()->create(['cover_path' => 'covers/catalog.jpg']);
    Storage::disk('public')->put('covers/catalog.jpg', 'catalog-image');

    $file = UploadedFile::fake()->createWithContent(
        'cover.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
    );

    $this->post(route('books.store'), [
        'title' => 'Con archivo',
        'shelf_id' => $shelf->id,
        'catalog_book_id' => $catalogBook->id,
        'cover' => $file,
    ])->assertRedirect();

    $book = Book::where('title', 'Con archivo')->firstOrFail();

    expect($book->cover_path)->not->toBeNull()
        ->and($book->cover_path)->not->toBe('covers/catalog.jpg');

    Storage::disk('public')->assertExists($book->cover_path);
});

it('rechaza un catalog_book_id inexistente', function () {
    $shelf = Shelf::factory()->for($this->user)->create();

    $this->post(route('books.store'), [
        'title' => 'Sin catálogo',
        'shelf_id' => $shelf->id,
        'catalog_book_id' => 999999,
    ])->assertSessionHasErrors('catalog_book_id');

    expect($this->user->books()->count())->toBe(0);
});

it('no permite cambiar el vínculo al catálogo al editar un libro', function () {
    $catalogBook = CatalogBook::factory()->create();
    $otherCatalogBook = CatalogBook::factory()->create();
    $book = Book::factory()->for($this->user)->create([
        'catalog_book_id' => $catalogBook->id,
    ]);

    $this->patch(route('books.update', $book), [
        'title' => $book->title,
        'catalog_book_id' => $otherCatalogBook->id,
    ])->assertRedirect();

    expect($book->fresh()->catalog_book_id)->toBe($catalogBook->id);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test --filter=BookTest
```

Expected: FAIL because `catalog_book_id` is not validated and the cover is not copied.

- [ ] **Step 3: Add the validation rule**

In `app/Http/Requests/StoreBookRequest.php`, add this line after `shelf_id`:

```php
'catalog_book_id' => ['nullable', 'integer', 'exists:catalog_books,id'],
```

Do **not** add `catalog_book_id` to `UpdateBookRequest`.

- [ ] **Step 4: Update `BookController::store`**

In `app/Http/Controllers/BookController.php`:

- Add `use App\Models\CatalogBook;`.
- Replace `store()` with:

```php
public function store(StoreBookRequest $request): RedirectResponse
{
    $this->ensureShelfOwnership($request, $request->integer('shelf_id'));

    $data = $request->validated();
    $data['user_id'] = $request->user()->id;

    $catalogBook = $request->filled('catalog_book_id')
        ? CatalogBook::find($request->integer('catalog_book_id'))
        : null;

    unset($data['cover'], $data['cover_url']);

    if ($request->hasFile('cover')) {
        $data['cover_path'] = $this->covers->storeUploaded($request->file('cover'));
    } elseif ($catalogBook?->cover_path) {
        $data['cover_path'] = $this->covers->copy($catalogBook->cover_path);
    } elseif ($request->filled('cover_url')) {
        $data['cover_path'] = $this->covers->storeFromUrl($request->input('cover_url'));
    }

    $data['catalog_book_id'] = $catalogBook?->id;

    $data['isbn'] = $request->filled('isbn')
        ? Isbn::normalize($request->input('isbn'))
        : null;

    $data['position'] = $this->placement->nextPosition((int) $data['shelf_id']);
    $data['view_mode'] = $request->user()->default_view_mode ?? 'covers';
    $nextBand = $this->placement->nextBand($request->user());
    $data['band'] = $nextBand['band'];
    $data['band_position'] = $nextBand['position'];

    $request->user()->books()->create($data);

    return redirect()->route('shelves.index');
}
```

- [ ] **Step 5: Run tests and verify they pass**

Run:

```bash
php artisan test --filter=BookTest
```

Expected: PASS.

- [ ] **Step 6: Pause for review**

Inform the user of the files modified. The user reviews and commits.

---

### Task 7: Selección del resultado del catálogo en Añadir libro

**Files:**
- Create: `resources/js/bookLookup.js`
- Create: `resources/js/bookLookup.test.js`
- Modify: `resources/js/Pages/Books/Create.vue`

- [ ] **Step 1: Write the failing Vitest test**

`resources/js/bookLookup.test.js`:

```js
import { describe, expect, it } from 'vitest';
import { applyLookupResult } from './bookLookup';

function emptyForm() {
    return {
        title: '',
        author: '',
        isbn: '',
        publisher: '',
        published_year: null,
        catalog_book_id: null,
        cover_url: 'https://old.test/cover.jpg',
        cover: 'old-file',
    };
}

describe('applyLookupResult', () => {
    it('selecciona un resultado del catálogo y limpia la url externa', () => {
        const form = emptyForm();

        const preview = applyLookupResult(form, {
            title: 'Libro local',
            author: 'Autora',
            isbn: '9788437604947',
            publisher: 'Editorial',
            published_year: 2020,
            cover_url: '/catalog-covers/7',
            catalog_book_id: 7,
        });

        expect(form.catalog_book_id).toBe(7);
        expect(form.cover_url).toBeNull();
        expect(form.cover).toBeNull();
        expect(preview).toBe('/catalog-covers/7');
        expect(form.title).toBe('Libro local');
    });

    it('selecciona un resultado de la api y conserva su portada', () => {
        const form = emptyForm();

        const preview = applyLookupResult(form, {
            title: 'Libro API',
            author: 'Autor',
            cover_url: 'https://covers.test/api.jpg',
        });

        expect(form.catalog_book_id).toBeNull();
        expect(form.cover_url).toBe('https://covers.test/api.jpg');
        expect(form.cover).toBeNull();
        expect(preview).toBe('https://covers.test/api.jpg');
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
npm run test -- bookLookup.test.js
```

Expected: FAIL because `resources/js/bookLookup.js` does not exist.

- [ ] **Step 3: Create the helper**

`resources/js/bookLookup.js`:

```js
export function applyLookupResult(form, book) {
    form.title = book.title ?? '';
    form.author = book.author ?? '';
    form.isbn = book.isbn ?? '';
    form.publisher = book.publisher ?? '';
    form.published_year = book.published_year ?? null;
    form.catalog_book_id = book.catalog_book_id ?? null;
    form.cover_url = book.catalog_book_id ? null : (book.cover_url ?? null);
    form.cover = null;

    return form.cover_url ?? book.cover_url ?? null;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
npm run test -- bookLookup.test.js
```

Expected: PASS, 2 tests.

- [ ] **Step 5: Use the helper in `Books/Create.vue`**

In `resources/js/Pages/Books/Create.vue`:

- Add the import:

```js
import { applyLookupResult } from '@/bookLookup';
```

- Add `catalog_book_id: null,` to the `useForm` object, after `published_year`.
- Replace the body of `select(book)` with:

```js
function select(book) {
    coverPreview.value = applyLookupResult(form, book);
    releaseCoverObjectUrl();
    if (coverInput.value) {
        coverInput.value.value = '';
    }
    scannerOpen.value = false;
    tab.value = 'manual';
}
```

- [ ] **Step 6: Run the full frontend suite and build**

Run:

```bash
npm run test
```

Expected: PASS, including the 2 new tests.

Run:

```bash
npm run build
```

Expected: build completes successfully.

- [ ] **Step 7: Pause for review**

Inform the user of the files created/modified. The user reviews and commits.

---

### Task 8: Páginas Vue del panel master

**Files:**
- Create: `resources/js/Components/CatalogBookForm.vue`
- Create: `resources/js/Pages/Catalog/Index.vue`
- Create: `resources/js/Pages/Catalog/Create.vue`
- Create: `resources/js/Pages/Catalog/Edit.vue`

No hay harness de pruebas de componentes en el proyecto. La verificación es `npm run build` y la revisión manual a 360 px y escritorio.

- [ ] **Step 1: Create the shared form component**

`resources/js/Components/CatalogBookForm.vue`:

```vue
<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    form: { type: Object, required: true },
    shelves: { type: Array, default: () => [] },
    showShelf: { type: Boolean, default: false },
    existingCoverUrl: { type: String, default: null },
});

const { t } = useI18n();
const coverInput = ref(null);
const localPreview = ref(null);
let objectUrl = null;

function releaseObjectUrl() {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
}

const preview = computed(
    () => localPreview.value || props.form.cover_url || props.existingCoverUrl || null
);

watch(
    () => props.form.cover_url,
    (value) => {
        if (!props.form.cover) {
            localPreview.value = value || props.existingCoverUrl || null;
        }
    }
);

function openCoverPicker() {
    coverInput.value?.click();
}

function onCoverChange(event) {
    const file = event.target.files[0] ?? null;
    props.form.cover = file;
    releaseObjectUrl();

    if (file) {
        objectUrl = URL.createObjectURL(file);
        localPreview.value = objectUrl;

        return;
    }

    localPreview.value = props.form.cover_url || props.existingCoverUrl || null;
}

onBeforeUnmount(releaseObjectUrl);
</script>

<template>
    <div class="flex flex-row items-start gap-3 sm:gap-6">
        <div class="flex w-24 shrink-0 flex-col items-center sm:w-[180px]">
            <div class="relative aspect-[2/3] w-full shrink-0 overflow-hidden rounded-card bg-surface shadow-card ring-1 ring-edge">
                <img
                    v-if="preview"
                    :src="preview"
                    :alt="t('ui.cover_alt', { title: form.title })"
                    class="h-full w-full object-cover"
                >
                <div v-else class="flex h-full items-center justify-center p-2 text-center font-serif text-sm text-ink sm:p-4">
                    {{ form.title || t('ui.show_as_cover') }}
                </div>

                <button
                    type="button"
                    class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-full border border-edge bg-surface-raised/90 text-ink shadow-card transition-colors hover:bg-surface hover:text-accent"
                    :aria-label="t('ui.change_cover')"
                    :title="t('ui.change_cover')"
                    @click="openCoverPicker"
                >
                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M2.5 5.5h2l1-1.5h5l1 1.5h2a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-11a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
                        <circle cx="8" cy="9" r="2.2" stroke="currentColor" stroke-width="1.3" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="min-w-0 flex-1 space-y-1">
            <input v-model="form.title" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.title_placeholder')">
            <p v-if="form.errors.title" class="text-xs text-danger">{{ form.errors.title }}</p>

            <input v-model="form.author" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.author_placeholder')">
            <p v-if="form.errors.author" class="text-xs text-danger">{{ form.errors.author }}</p>

            <input v-model="form.isbn" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.isbn')">
            <p v-if="form.errors.isbn" class="text-xs text-danger">{{ form.errors.isbn }}</p>

            <input v-model="form.publisher" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.publisher_placeholder')">
            <p v-if="form.errors.publisher" class="text-xs text-danger">{{ form.errors.publisher }}</p>

            <input v-model.number="form.published_year" type="number" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.year_placeholder')">
            <p v-if="form.errors.published_year" class="text-xs text-danger">{{ form.errors.published_year }}</p>

            <template v-if="showShelf">
                <select v-model="form.shelf_id" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink sm:px-3 sm:py-3 sm:text-base">
                    <option v-for="shelf in shelves" :key="shelf.id" :value="shelf.id">
                        {{ shelf.display_name ?? shelf.name }}
                    </option>
                </select>
                <p class="text-xs text-ink-muted">{{ t('ui.catalog_shelf_help') }}</p>
                <p v-if="form.errors.shelf_id" class="text-xs text-danger">{{ form.errors.shelf_id }}</p>
            </template>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        <label class="block text-sm text-ink-muted" for="catalog-cover-url">
            {{ t('ui.catalog_cover_url') }}
        </label>
        <input
            id="catalog-cover-url"
            v-model="form.cover_url"
            type="url"
            class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-muted"
            :placeholder="t('ui.catalog_cover_url')"
        >
        <p v-if="form.errors.cover_url" class="text-xs text-danger">{{ form.errors.cover_url }}</p>

        <input
            ref="coverInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="onCoverChange"
        >
        <p v-if="form.errors.cover" class="text-xs text-danger">{{ form.errors.cover }}</p>
    </div>
</template>
```

- [ ] **Step 2: Create the listing page**

`resources/js/Pages/Catalog/Index.vue`:

```vue
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    books: { type: Array, required: true },
});

const { t } = useI18n();
const deleting = ref(null);

function confirmDelete(book) {
    deleting.value = book;
}

function destroy() {
    if (!deleting.value) {
        return;
    }

    router.delete(`/catalog/${deleting.value.id}`, {
        onFinish: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('ui.catalog_heading')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.catalog_heading') }}</h1>
                <Link
                    href="/catalog/create"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.catalog_add') }}
                </Link>
            </div>

            <p v-if="books.length === 0" class="rounded-card border border-edge bg-surface p-6 text-center text-ink-muted">
                {{ t('ui.catalog_empty') }}
            </p>

            <ul v-else class="space-y-2">
                <li
                    v-for="book in books"
                    :key="book.id"
                    class="flex items-start gap-3 rounded-control border border-edge bg-surface p-3"
                >
                    <span class="relative flex h-20 w-14 shrink-0 items-center justify-center overflow-hidden rounded bg-surface-raised font-serif text-xl text-accent shadow-soft">
                        {{ book.title.charAt(0) }}
                        <img
                            v-if="book.cover_url"
                            :src="book.cover_url"
                            :alt="t('ui.cover_alt', { title: book.title })"
                            class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >
                    </span>

                    <div class="min-w-0 flex-1">
                        <strong class="block font-serif text-base leading-snug">{{ book.title }}</strong>
                        <span class="block text-sm text-ink-muted">{{ book.author || t('ui.unknown_author') }}</span>
                        <span v-if="book.publisher || book.published_year" class="mt-1 block text-xs text-ink-muted/80">
                            {{ [book.publisher, book.published_year].filter(Boolean).join(' · ') }}
                        </span>
                        <span v-if="book.isbn" class="mt-1 block text-xs text-ink-muted/80">{{ book.isbn }}</span>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                        <Link
                            :href="`/catalog/${book.id}/edit`"
                            class="rounded-control border border-edge px-3 py-2 text-sm text-ink hover:bg-surface-raised"
                        >
                            {{ t('ui.edit_book_title') }}
                        </Link>
                        <button
                            type="button"
                            class="rounded-control border border-danger px-3 py-2 text-sm text-danger hover:bg-danger/10"
                            @click="confirmDelete(book)"
                        >
                            {{ t('ui.delete') }}
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <Modal :show="deleting !== null" max-width="md" @close="deleting = null">
            <div class="p-6">
                <h2 class="font-serif text-lg">{{ t('ui.catalog_delete_title') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ t('ui.catalog_delete_confirm') }}</p>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                        @click="deleting = null"
                    >
                        {{ t('ui.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-control bg-danger px-4 py-2 text-on-accent hover:bg-danger-strong"
                        @click="destroy"
                    >
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 3: Create the create page**

`resources/js/Pages/Catalog/Create.vue`:

```vue
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import CatalogBookForm from '@/Components/CatalogBookForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    shelves: { type: Array, required: true },
});

const { t } = useI18n();

const form = useForm({
    title: '',
    author: '',
    isbn: '',
    publisher: '',
    published_year: null,
    shelf_id: props.shelves[0]?.id ?? null,
    cover: null,
    cover_url: null,
});

function submit() {
    form.post('/catalog', { forceFormData: true });
}
</script>

<template>
    <Head :title="t('ui.catalog_add')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.catalog_add') }}</h1>
                <Link
                    href="/catalog"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.back') }}
                </Link>
            </div>

            <div
                v-if="props.shelves.length === 0"
                class="rounded-card border border-edge bg-surface p-4 text-sm text-ink-muted"
            >
                {{ t('ui.catalog_no_shelves') }}
                <Link href="/shelves" class="text-accent underline">{{ t('ui.nav_shelves') }}</Link>
            </div>

            <form v-else class="w-full" @submit.prevent="submit">
                <CatalogBookForm :form="form" :shelves="props.shelves" show-shelf />

                <button
                    type="submit"
                    class="mt-4 rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                    :disabled="form.processing"
                >
                    {{ t('ui.save') }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 4: Create the edit page**

`resources/js/Pages/Catalog/Edit.vue`:

```vue
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import CatalogBookForm from '@/Components/CatalogBookForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
});

const { t } = useI18n();

const form = useForm({
    title: props.book.title ?? '',
    author: props.book.author ?? '',
    isbn: props.book.isbn ?? '',
    publisher: props.book.publisher ?? '',
    published_year: props.book.published_year ?? null,
    cover: null,
    cover_url: null,
});

function submit() {
    form.patch(`/catalog/${props.book.id}`, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="t('ui.catalog_edit_title')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.catalog_edit_title') }}</h1>
                <Link
                    href="/catalog"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.back') }}
                </Link>
            </div>

            <form class="w-full" @submit.prevent="submit">
                <CatalogBookForm :form="form" :existing-cover-url="book.cover_url" />

                <button
                    type="submit"
                    class="mt-4 rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                    :disabled="form.processing"
                >
                    {{ t('ui.save') }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 5: Build and verify**

Run:

```bash
npm run build
```

Expected: build completes successfully.

- [ ] **Step 6: Pause for review**

Inform the user of the files created. The user reviews and commits.

---

### Task 9: Textos y navegación

**Files:**
- Modify: `lang/es.json`
- Modify: `lang/en.json`
- Modify: `resources/js/Components/AppShell.vue`
- Modify: `resources/js/Components/BottomNav.vue`

- [ ] **Step 1: Add the Spanish keys**

In `lang/es.json`, insert these keys in alphabetical order, after the existing `ui.cards_*` block and before `ui.change_cover`:

```json
"ui.catalog_add": "Añadir al catálogo",
"ui.catalog_cover_error": "No se pudo descargar la portada. Revisa la URL.",
"ui.catalog_cover_url": "URL de la portada",
"ui.catalog_created": "Libro añadido al catálogo y a tu estantería.",
"ui.catalog_delete_confirm": "¿Eliminar esta ficha del catálogo? Las copias en las estanterías se conservarán.",
"ui.catalog_delete_title": "Eliminar ficha",
"ui.catalog_deleted": "Ficha del catálogo eliminada.",
"ui.catalog_edit_title": "Editar ficha del catálogo",
"ui.catalog_empty": "Aún no has añadido libros al catálogo.",
"ui.catalog_heading": "Catálogo master",
"ui.catalog_isbn_invalid": "El ISBN no es válido.",
"ui.catalog_no_shelves": "Necesitas una estantería para añadir el libro.",
"ui.catalog_shelf_help": "El libro también se añadirá a esta estantería.",
"ui.catalog_updated": "Ficha del catálogo actualizada.",
```

Insert this key in alphabetical order among the `ui.nav_*` keys, after `ui.nav_cards` and before `ui.nav_decorations`:

```json
"ui.nav_catalog": "Catálogo",
```

- [ ] **Step 2: Add the English keys**

In `lang/en.json`, insert the same keys with these values, in the same positions:

```json
"ui.catalog_add": "Add to catalog",
"ui.catalog_cover_error": "The cover could not be downloaded. Check the URL.",
"ui.catalog_cover_url": "Cover URL",
"ui.catalog_created": "Book added to the catalog and to your shelf.",
"ui.catalog_delete_confirm": "Delete this catalog entry? The copies on shelves will be kept.",
"ui.catalog_delete_title": "Delete entry",
"ui.catalog_deleted": "Catalog entry deleted.",
"ui.catalog_edit_title": "Edit catalog entry",
"ui.catalog_empty": "You have not added any books to the catalog yet.",
"ui.catalog_heading": "Master catalog",
"ui.catalog_isbn_invalid": "The ISBN is not valid.",
"ui.catalog_no_shelves": "You need a shelf to add the book.",
"ui.catalog_shelf_help": "The book will also be added to this shelf.",
"ui.catalog_updated": "Catalog entry updated.",
"ui.nav_catalog": "Catalog",
```

- [ ] **Step 3: Add the desktop navigation link**

In `resources/js/Components/AppShell.vue`, replace `links` with:

```js
const links = computed(() => [
    { label: t('ui.nav_library'), href: '/' },
    { label: t('ui.nav_shelves'), href: '/shelves' },
    ...(user.value?.is_master
        ? [{ label: t('ui.nav_catalog'), href: '/catalog' }]
        : []),
    { label: t('ui.nav_cards'), href: '/cards' },
    { label: t('ui.nav_decorations'), href: '/decorations' },
    { label: t('ui.nav_album'), href: '/album' },
    { label: t('ui.nav_settings'), href: '/settings' },
]);
```

- [ ] **Step 4: Add the mobile menu link**

In `resources/js/Components/BottomNav.vue`:

- Replace the plain `const menuItems = [...]` array with a computed:

```js
const menuItems = computed(() => [
    { label: t('ui.nav_library'), href: '/', iconPath: 'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z' },
    { label: t('ui.nav_shelves'), href: '/shelves', iconPath: 'M4 5h16v14H4zM7 5h2v14H7zM12 5h2v14h-2zM17 5h2v14h-2z' },
    ...(page.props.auth.user?.is_master
        ? [{ label: t('ui.nav_catalog'), href: '/catalog', iconPath: 'M4 6h16M4 12h16M4 18h16' }]
        : []),
    { label: t('ui.nav_cards'), href: '/cards', iconPath: 'M5 2h14a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zM8 5h5v8H8zM16 6h1M16 9h1M8 16h8v3H8z' },
    { label: t('ui.nav_decorations'), href: '/decorations', iconPath: 'M12 3l1.9 5.8L20 10l-6.1 1.2L12 17l-1.9-5.8L4 10l6.1-1.2z' },
    { label: t('ui.nav_album'), href: '/album', iconPath: 'M2 7h20v10H2V7zm2 2h16v6H4V9z' },
    { label: t('ui.nav_settings'), href: '/settings', iconPath: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z' },
]);
```

`computed` and `usePage` are already imported in that file.

- [ ] **Step 5: Run translation and build checks**

Run:

```bash
php artisan test --filter=CatalogTest
```

Expected: PASS, including the key-completeness test for `es` and `en`.

Run:

```bash
npm run build
```

Expected: build completes successfully.

- [ ] **Step 6: Pause for review**

Inform the user of the files modified. The user reviews and commits.

---

### Task 10: Documentación visual y verificación completa

**Files:**
- Modify: `docs/DISENO.md`
- Modify: `docs/superpowers/specs/2026-11-12-catalogo-master-design.md`

- [ ] **Step 1: Register the visual change**

Add this row at the end of the change log in `docs/DISENO.md`:

```markdown
| 2026-11-12 | Nuevo panel **Catálogo master**, visible solo para la cuenta master: enlace en la navegación lateral y en el menú móvil, listado de fichas con portada y acciones, formulario compartido de alta/edición y estado vacío. Reutiliza tokens y componentes existentes; sin colores ni tipografías nuevas. | Agente |
```

- [ ] **Step 2: Update the spec status**

In `docs/superpowers/specs/2026-11-12-catalogo-master-design.md`, change:

```markdown
**Estado:** Diseño aprobado por la usuaria; pendiente de implementación.
```

to:

```markdown
**Estado:** Implementado y verificado.
```

- [ ] **Step 3: Run the complete backend suite**

Run:

```bash
php artisan test
```

Expected: PASS, all tests.

- [ ] **Step 4: Run the complete frontend suite**

Run:

```bash
npm run test
```

Expected: PASS, all tests.

- [ ] **Step 5: Run the production build**

Run:

```bash
npm run build
```

Expected: build completes successfully.

- [ ] **Step 6: Format PHP**

Run:

```bash
php vendor/bin/pint
```

Expected: Pint finishes without errors. If it changes files, review the changes before reporting.

- [ ] **Step 7: Manual acceptance checks**

Antes de probar el panel, activa tu cuenta como master en local con el SQL de la sección 5.1 del spec (sustituye el correo de ejemplo; no escribas tu correo real en el repositorio).

Ask the user to verify:

- [ ] A 360 px: el enlace **Catálogo** aparece dentro del menú de la barra inferior solo con la cuenta master.
- [ ] En escritorio: el enlace **Catálogo** aparece en la barra lateral solo con la cuenta master.
- [ ] La cuenta normal no ve el enlace y recibe `403` al abrir `/catalog`.
- [ ] La master crea una ficha con portada y comprueba que aparece en la estantería elegida.
- [ ] Otra cuenta busca el título y ve la ficha local antes que los resultados de la API.
- [ ] La portada se ve en los resultados y en la estantería.
- [ ] Editar o eliminar la ficha no cambia la copia personal.
- [ ] La portada del catálogo devuelve `404` sin autenticación.

- [ ] **Step 8: Report and hand off**

Inform the user of every file created and modified, the verification output, and any manual checks pending. The user reviews and commits.

---

## Self-review

- **Spec coverage:** Tasks 1–4 cover migrations, models, permissions, panel CRUD and personal copy. Task 5 covers local search and translation rules. Task 6 covers `catalog_book_id` and cover priority. Task 7 covers frontend selection. Task 8 covers pages/form. Task 9 covers i18n and navigation. Task 10 covers visual docs and verification. Manual acceptance checks mirror section 16 of the spec.
- **Placeholder scan:** No TBD/TODO placeholders. Every code step includes complete code.
- **Type consistency:** `CatalogBook`, `catalog_books`, `catalog_book_id`, `cover_path`, `catalog_book_id` in lookup results, `BookPlacementService::nextPosition/nextBand`, `ShelfDisplayService::forUser`, `CoverService::copy` and `applyLookupResult` are used consistently across tasks.
