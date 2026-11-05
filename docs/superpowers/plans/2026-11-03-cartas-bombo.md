# El bombo de las cartas — Plan de implementación (fase 1)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar el módulo **Cartas**: una tirada diaria de balotas misteriosas, cartas largas de personajes de los libros de la usuaria, álbum y tinta por repetidos.

**Architecture:** Catálogo en `config/cards.php` y cartas en `resources/cards/{clave}/{locale}.md`. `CardCatalogService` empareja libros y resuelve metadatos; `CardDeckService` genera balotas deterministas y ejecuta la tirada en una transacción. Tres tablas (`card_draws`, `user_cards`, `user_wallets`) y dos endpoints JSON (`GET /cards/{card}`, `POST /cards/draw`). La pantalla `/cards` es Inertia + Vue.

**Tech Stack:** Laravel 13 (PHP 8.3), Inertia 2, Vue 3 `<script setup>`, Tailwind CSS 4, Pest, Vitest, MySQL.

**Reglas del repo (AGENTS.md):** la usuaria hace **todos** los commits. No ejecutar `git add`, `git commit`, `git push` ni `git tag`. Cada tarea termina con las pruebas en verde y los archivos listos, nunca con un commit.

**Shell:** Windows PowerShell 5.1. Los comandos se ejecutan con el envoltorio `cmd.exe /C '...'` desde la raíz `D:\Documentos\Proyectos_jhenny2\biblioteca`. No usar `&&` ni tuberías dentro de PowerShell.

**Spec:** `docs/superpowers/specs/2026-11-03-cartas-bombo-design.md`.

---

## Estructura de archivos

**Backend**
- `config/cards.php` — zona horaria, balotas diarias, tinta, familias y 20 cartas.
- `app/Services/CardCatalogService.php` — normalización, emparejamiento, pool, metadatos, carta.
- `app/Services/CardDeckService.php` — día, reinicio, balotas deterministas, tirada.
- `app/Models/CardDraw.php`, `app/Models/UserCard.php`, `app/Models/UserWallet.php`.
- `app/Models/User.php` — relaciones `cardDraws()`, `userCards()`, `wallet()`.
- `app/Http/Controllers/CardController.php` — `index` (Inertia) y `show` (JSON).
- `app/Http/Controllers/CardDrawController.php` — `store` (JSON).
- `routes/web.php` — tres rutas dentro de `auth` + `nickname`.
- `database/migrations/2026_11_03_000002_create_card_draws_table.php`.
- `database/migrations/2026_11_03_000003_create_user_cards_table.php`.
- `database/migrations/2026_11_03_000004_create_user_wallets_table.php`.

**Contenido**
- `resources/cards/{clave}/{locale}.md` — 20 × 8 = 160 cartas largas.

**Frontend**
- `resources/js/Pages/Cards/Index.vue` — página, estado y peticiones.
- `resources/js/Components/CardMachine.vue` — bombo, balotas, manivela, cuenta atrás.
- `resources/js/Components/CardEnvelope.vue` — sobre con lacre.
- `resources/js/Components/CardLetterModal.vue` — carta en pergamino.
- `resources/js/Components/CardAlbum.vue` — gabinete y ranuras.
- `resources/js/Components/CardEmblem.vue` — 20 emblemas SVG lineales.
- `resources/js/cards.js` — `formatCountdown`.
- `resources/js/Components/AppShell.vue` — enlace Cartas.
- `resources/css/textures.css` — clases del bombo, sobre, carta y álbum.

**Idiomas y docs**
- `lang/{es,en,pt,fr,de,it,ca,gl}.json` — 30 claves nuevas.
- `docs/DISENO.md` — sección Cartas y registro de cambios.

**Pruebas**
- `tests/Feature/CardCatalogTest.php`
- `tests/Feature/CardDrawTest.php`
- `tests/Feature/CardPageTest.php`
- `tests/Feature/CardContentTest.php`
- `resources/js/cards.test.js`
- `resources/js/Components/__tests__/CardMachine.test.js`
- `resources/js/Components/__tests__/CardAlbum.test.js`
- `resources/js/Components/__tests__/CardLetterModal.test.js`
- `resources/js/Components/__tests__/AppShell.test.js` (ampliar)

**Patrón de pruebas de componentes:** los componentes que usan `useI18n`
necesitan mockear Inertia:

```js
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { locale: 'es', translations: {} } }),
}));
```

Usar `enableAutoUnmount(afterEach)` para limpiar intervalos y temporizadores.
`CardLetterModal` usa `Modal`; en su prueba, stubear `Modal` con un contenedor
que renderice el slot para evitar `HTMLDialogElement.showModal()` en jsdom:

```js
global: {
    stubs: {
        Modal: {
            props: ['show'],
            template: '<div v-if="show"><slot /></div>',
        },
    },
},
```

---

## Task 1: Configuración y catálogo

**Files:**
- Create: `config/cards.php`
- Create: `app/Services/CardCatalogService.php`
- Test: `tests/Feature/CardCatalogTest.php`

- [ ] **Step 1: Escribir la prueba que falla**

Crear `tests/Feature/CardCatalogTest.php`:

```php
<?php

use App\Models\Book;
use App\Models\User;
use App\Services\CardCatalogService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->catalog = app(CardCatalogService::class);
});

it('normaliza títulos con tildes y paréntesis', function () {
    expect($this->catalog->normalize('Alas de ónix (Empíreo 3)'))->toBe('alas de onix')
        ->and($this->catalog->normalize('  El día de la Trilla '))->toBe('el dia de la trilla');
});

it('activa la familia Empíreo con Alas de onix sin tilde', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de onix',
        'author' => 'Rebecca Yarros',
    ]);

    $pool = $this->catalog->poolFor($this->user);

    expect($pool)->toHaveCount(9)
        ->and($pool)->toContain('violet', 'xaden', 'tairn', 'andarna', 'bodhi');
});

it('activa la familia biblioteca con el coautor en el campo author', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'La biblioteca de la medianoche',
        'author' => 'Matt Haig, Miguel Marqués Muñoz',
    ]);

    expect($this->catalog->poolFor($this->user))->toContain('nora', 'mrs_elm', 'voltaire', 'ash');
});

it('activa la familia perfectos con el autor tal como está en la base', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'Mentiras y secretos',
        'author' => 'Álex Ramírez',
    ]);

    expect($this->catalog->poolFor($this->user))->toContain('jude', 'aegan', 'adrik', 'alex');
});

it('no duplica cartas cuando hay varios libros de la misma familia', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de sangre',
        'author' => 'Rebecca Yarros',
    ]);
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de hierro (Empíreo 2)',
        'author' => 'Rebecca Yarros',
    ]);

    expect($this->catalog->poolFor($this->user))->toHaveCount(9);
});

it('deja el pool vacío si ningún libro tiene cartas', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'Un libro cualquiera',
        'author' => 'Alguien',
    ]);

    expect($this->catalog->poolFor($this->user))->toBe([]);
});

it('devuelve metadatos completos para las 20 cartas', function () {
    foreach (array_keys(config('cards.cards')) as $key) {
        $meta = $this->catalog->metadata($key);

        expect($meta)->toHaveKeys(['key', 'family', 'familyLabel', 'familyEmblem', 'character', 'monogram', 'emblem'])
            ->and($meta['character'])->not->toBe('')
            ->and($meta['monogram'])->not->toBe('')
            ->and($meta['familyEmblem'])->not->toBe('')
            ->and($meta['emblem'])->not->toBe('');
    }
});
```

- [ ] **Step 2: Ejecutar la prueba y ver el fallo**

```bash
cmd.exe /C 'php artisan test --filter=CardCatalogTest'
```

Esperado: falla porque `config/cards.php` y el servicio no existen.

- [ ] **Step 3: Crear `config/cards.php`**

Estructura completa (20 cartas, 4 familias):

```php
<?php

return [
    'timezone' => env('CARDS_TIMEZONE', 'America/Bogota'),
    'daily_balls' => 8,
    'ink_per_duplicate' => 1,

    'families' => [
        'empireo' => [
            'label' => 'Empíreo',
            'emblem' => 'dragon',
            'titles' => ['Alas de sangre', 'Alas de hierro', 'Alas de ónix', 'El día de la Trilla'],
            'authors' => ['Rebecca Yarros'],
        ],
        'mapa' => [
            'label' => 'El mapa de los anhelos',
            'emblem' => 'map',
            'titles' => ['El mapa de los anhelos'],
            'authors' => ['Alice Kellen'],
        ],
        'biblioteca' => [
            'label' => 'La biblioteca de la medianoche',
            'emblem' => 'moon',
            'titles' => ['La biblioteca de la medianoche'],
            'authors' => ['Matt Haig'],
        ],
        'perfectos' => [
            'label' => 'Perfectos mentirosos',
            'emblem' => 'mask',
            'titles' => ['Mentiras y secretos', 'Perfectos mentirosos'],
            'authors' => ['Álex Ramírez', 'Álex Mírez'],
        ],
    ],

    'cards' => [
        'violet' => ['family' => 'empireo', 'character' => 'Violet Sorrengail', 'monogram' => 'VS', 'emblem' => 'dragon'],
        'xaden' => ['family' => 'empireo', 'character' => 'Xaden Riorson', 'monogram' => 'XR', 'emblem' => 'shadow'],
        'tairn' => ['family' => 'empireo', 'character' => 'Tairn', 'monogram' => 'T', 'emblem' => 'wing'],
        'andarna' => ['family' => 'empireo', 'character' => 'Andarna', 'monogram' => 'A', 'emblem' => 'feather'],
        'rhiannon' => ['family' => 'empireo', 'character' => 'Rhiannon Matthias', 'monogram' => 'RM', 'emblem' => 'shield'],
        'liam' => ['family' => 'empireo', 'character' => 'Liam Mairi', 'monogram' => 'LM', 'emblem' => 'sword'],
        'dain' => ['family' => 'empireo', 'character' => 'Dain Aetos', 'monogram' => 'DA', 'emblem' => 'compass'],
        'imogen' => ['family' => 'empireo', 'character' => 'Imogen Cardulo', 'monogram' => 'IC', 'emblem' => 'flame'],
        'bodhi' => ['family' => 'empireo', 'character' => 'Bodhi Durran', 'monogram' => 'BD', 'emblem' => 'knot'],
        'grace' => ['family' => 'mapa', 'character' => 'Grace Peterson', 'monogram' => 'GP', 'emblem' => 'map'],
        'will' => ['family' => 'mapa', 'character' => 'Will Tucker', 'monogram' => 'WT', 'emblem' => 'lantern'],
        'lucy' => ['family' => 'mapa', 'character' => 'Lucy', 'monogram' => 'L', 'emblem' => 'star'],
        'nora' => ['family' => 'biblioteca', 'character' => 'Nora Seed', 'monogram' => 'NS', 'emblem' => 'moon'],
        'mrs_elm' => ['family' => 'biblioteca', 'character' => 'Mrs. Elm', 'monogram' => 'ME', 'emblem' => 'chess'],
        'voltaire' => ['family' => 'biblioteca', 'character' => 'Voltaire', 'monogram' => 'V', 'emblem' => 'cat'],
        'ash' => ['family' => 'biblioteca', 'character' => 'Ash', 'monogram' => 'A', 'emblem' => 'music'],
        'jude' => ['family' => 'perfectos', 'character' => 'Jude Derry', 'monogram' => 'JD', 'emblem' => 'mask'],
        'aegan' => ['family' => 'perfectos', 'character' => 'Aegan Cash', 'monogram' => 'AG', 'emblem' => 'crown'],
        'adrik' => ['family' => 'perfectos', 'character' => 'Adrik Cash', 'monogram' => 'AD', 'emblem' => 'key'],
        'alex' => ['family' => 'perfectos', 'character' => 'Alex Cash', 'monogram' => 'AL', 'emblem' => 'rose'],
    ],
];
```

- [ ] **Step 4: Crear `app/Services/CardCatalogService.php`**

```php
<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Str;

class CardCatalogService
{
    public function families(): array
    {
        return config('cards.families');
    }

    public function cards(): array
    {
        return config('cards.cards');
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->cards());
    }

    public function normalize(string $value): string
    {
        $value = preg_replace('/\([^)]*\)/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', Str::slug($value, ' ')) ?? '');
    }

    public function familyForBook(string $title, ?string $author): ?string
    {
        $normalizedTitle = $this->normalize($title);
        $normalizedAuthor = $author ? $this->normalize($author) : null;

        foreach ($this->families() as $key => $family) {
            $titles = array_map(fn (string $value) => $this->normalize($value), $family['titles']);

            if (! in_array($normalizedTitle, $titles, true)) {
                continue;
            }

            if ($normalizedAuthor === null || $family['authors'] === []) {
                return $key;
            }

            foreach ($family['authors'] as $candidate) {
                if (str_contains($normalizedAuthor, $this->normalize($candidate))) {
                    return $key;
                }
            }
        }

        return null;
    }

    public function poolFor(User $user): array
    {
        $families = $user->books()
            ->get(['title', 'author'])
            ->map(fn (Book $book) => $this->familyForBook($book->title, $book->author))
            ->filter()
            ->unique()
            ->all();

        return collect($this->cards())
            ->filter(fn (array $card) => in_array($card['family'], $families, true))
            ->keys()
            ->all();
    }

    public function metadata(string $key): array
    {
        $card = $this->cards()[$key];
        $family = $this->families()[$card['family']];

        return [
            'key' => $key,
            'family' => $card['family'],
            'familyLabel' => $family['label'],
            'familyEmblem' => $family['emblem'],
            'character' => $card['character'],
            'monogram' => $card['monogram'],
            'emblem' => $card['emblem'],
        ];
    }

    public function inPool(User $user, string $key): bool
    {
        return in_array($key, $this->poolFor($user), true);
    }

    public function isUnlocked(User $user, string $key): bool
    {
        return $user->userCards()->where('card_key', $key)->exists();
    }

    public function letter(string $key, string $locale): ?string
    {
        if (! $this->exists($key)) {
            return null;
        }

        $curated = config('languages.curated');
        $candidates = in_array($locale, $curated, true) ? [$locale, 'es'] : ['es'];

        foreach (array_unique($candidates) as $candidate) {
            $path = resource_path("cards/{$key}/{$candidate}.md");

            if (is_file($path)) {
                return trim((string) file_get_contents($path));
            }
        }

        return null;
    }
}
```

- [ ] **Step 5: Crear la semilla de la primera carta**

Crear `resources/cards/violet/es.md` con la carta de Violet en español (250+
palabras, según el brief de la tarea 11). Esta semilla permite que las pruebas
de la API abran una carta antes de completar el contenido. En la tarea 11 se
traducirá a los otros 7 idiomas.

- [ ] **Step 6: Ejecutar la prueba y ver el verde**

```bash
cmd.exe /C 'php artisan test --filter=CardCatalogTest'
```

- [ ] **Step 7: Pint**

```bash
cmd.exe /C 'vendor\bin\pint app\Services\CardCatalogService.php config\cards.php tests\Feature\CardCatalogTest.php'
```

---

## Task 2: Tablas, modelos y relaciones

**Files:**
- Create: `database/migrations/2026_11_03_000002_create_card_draws_table.php`
- Create: `database/migrations/2026_11_03_000003_create_user_cards_table.php`
- Create: `database/migrations/2026_11_03_000004_create_user_wallets_table.php`
- Create: `app/Models/CardDraw.php`
- Create: `app/Models/UserCard.php`
- Create: `app/Models/UserWallet.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/CardDrawTest.php`

- [ ] **Step 1: Escribir la prueba de modelo que falla**

Crear `tests/Feature/CardDrawTest.php` con las pruebas de persistencia:

```php
<?php

use App\Models\CardDraw;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserWallet;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('guarda una tirada única por usuaria y día', function () {
    CardDraw::create([
        'user_id' => $this->user->id,
        'draw_date' => '2026-11-03',
        'card_key' => 'violet',
        'ball_index' => 0,
        'duplicate' => false,
        'ink_earned' => 0,
    ]);

    expect($this->user->cardDraws()->count())->toBe(1);
});

it('relaciona cartas y cartera con la usuaria', function () {
    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => 'violet',
        'copies' => 2,
        'first_unlocked_at' => now(),
    ]);
    UserWallet::create(['user_id' => $this->user->id, 'ink' => 3]);

    expect($this->user->userCards()->count())->toBe(1)
        ->and($this->user->userCards()->first()->copies)->toBe(2)
        ->and($this->user->wallet->ink)->toBe(3);
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

```bash
cmd.exe /C 'php artisan test --filter=CardDrawTest'
```

- [ ] **Step 3: Crear las migraciones**

`2026_11_03_000002_create_card_draws_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('draw_date');
            $table->string('card_key', 64);
            $table->unsignedTinyInteger('ball_index');
            $table->boolean('duplicate')->default(false);
            $table->unsignedTinyInteger('ink_earned')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'draw_date']);
            $table->index('card_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_draws');
    }
};
```

`2026_11_03_000003_create_user_cards_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('card_key', 64);
            $table->unsignedInteger('copies')->default(1);
            $table->timestamp('first_unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'card_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_cards');
    }
};
```

`2026_11_03_000004_create_user_wallets_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('ink')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_wallets');
    }
};
```

- [ ] **Step 4: Crear los modelos**

`app/Models/CardDraw.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardDraw extends Model
{
    protected $fillable = ['user_id', 'draw_date', 'card_key', 'ball_index', 'duplicate', 'ink_earned'];

    protected function casts(): array
    {
        return [
            'draw_date' => 'date',
            'duplicate' => 'boolean',
            'ink_earned' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

`app/Models/UserCard.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCard extends Model
{
    protected $fillable = ['user_id', 'card_key', 'copies', 'first_unlocked_at'];

    protected function casts(): array
    {
        return [
            'copies' => 'integer',
            'first_unlocked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

`app/Models/UserWallet.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWallet extends Model
{
    protected $fillable = ['user_id', 'ink'];

    protected function casts(): array
    {
        return ['ink' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Añadir relaciones a `User`**

```php
public function cardDraws(): HasMany
{
    return $this->hasMany(CardDraw::class);
}

public function userCards(): HasMany
{
    return $this->hasMany(UserCard::class);
}

public function wallet(): HasOne
{
    return $this->hasOne(UserWallet::class);
}
```

Importar `HasOne` en `app/Models/User.php`.

- [ ] **Step 6: Ejecutar migraciones y pruebas**

```bash
cmd.exe /C 'php artisan migrate'
cmd.exe /C 'php artisan test --filter=CardDrawTest'
```

- [ ] **Step 7: Pint**

```bash
cmd.exe /C 'vendor\bin\pint app\Models database\migrations tests\Feature\CardDrawTest.php'
```

---

## Task 3: Balotas deterministas y tirada

**Files:**
- Create: `app/Services/CardDeckService.php`
- Modify: `tests/Feature/CardDrawTest.php`

- [ ] **Step 1: Ampliar `CardDrawTest` con las reglas de la tirada**

```php
use App\Models\Book;
use App\Services\CardCatalogService;
use App\Services\CardDeckService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

function giveEmpyreanBooks(User $user): void
{
    Book::factory()->for($user)->create([
        'title' => 'Alas de sangre',
        'author' => 'Rebecca Yarros',
    ]);
}

it('genera las mismas balotas para la misma usuaria y día', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $day = CarbonImmutable::parse('2026-11-03', 'UTC');

    $first = $deck->offersFor($this->user, $day);
    $second = $deck->offersFor($this->user, $day);

    expect($first)->toBe($second)
        ->and($first)->toHaveCount(8);
});

it('cambia las balotas al día siguiente', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);

    $first = $deck->offersFor($this->user, CarbonImmutable::parse('2026-11-03', 'UTC'));
    $second = $deck->offersFor($this->user, CarbonImmutable::parse('2026-11-04', 'UTC'));

    expect($first)->not->toBe($second);
});

it('muestra todas las balotas si el pool tiene menos de ocho', function () {
    Book::factory()->for($this->user)->create([
        'title' => 'El mapa de los anhelos',
        'author' => 'Alice Kellen',
    ]);

    expect(app(CardDeckService::class)->offersFor($this->user))->toHaveCount(3);
});

it('entrega una carta nueva y no da tinta', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $offers = $deck->offersFor($this->user);
    $result = $deck->draw($this->user, 0);

    expect($result['card']['key'])->toBe($offers[0])
        ->and($result['duplicate'])->toBeFalse()
        ->and($result['copies'])->toBe(1)
        ->and($result['ink'])->toBe(0)
        ->and($result['inkEarned'])->toBe(0);

    expect($this->user->userCards()->count())->toBe(1)
        ->and($this->user->wallet->ink)->toBe(0);
});

it('convierte un repetido en una copia y una tinta', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $offers = $deck->offersFor($this->user);

    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => $offers[0],
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);

    $result = $deck->draw($this->user, 0);

    expect($result['duplicate'])->toBeTrue()
        ->and($result['copies'])->toBe(2)
        ->and($result['inkEarned'])->toBe(1)
        ->and($result['ink'])->toBe(1);
});

it('rechaza una segunda tirada el mismo día', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $deck->draw($this->user, 0);

    expect(fn () => $deck->draw($this->user, 1))
        ->toThrow(ValidationException::class);
});

it('rechaza una balota fuera de rango', function () {
    giveEmpyreanBooks($this->user);

    expect(fn () => app(CardDeckService::class)->draw($this->user, 99))
        ->toThrow(ValidationException::class);
});

it('rechaza la tirada si no hay cartas', function () {
    expect(fn () => app(CardDeckService::class)->draw($this->user, 0))
        ->toThrow(ValidationException::class);
});

it('usa la zona horaria configurada para el cambio de día', function () {
    config(['cards.timezone' => 'America/Bogota']);
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-04 04:59:00', 'UTC'));
    expect($deck->today()->toDateString())->toBe('2026-11-03');

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-04 05:00:00', 'UTC'));
    expect($deck->today()->toDateString())->toBe('2026-11-04');

    CarbonImmutable::setTestNow();
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

```bash
cmd.exe /C 'php artisan test --filter=CardDrawTest'
```

- [ ] **Step 3: Crear `app/Services/CardDeckService.php`**

```php
<?php

namespace App\Services;

use App\Models\CardDraw;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserWallet;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Random\Engine\Mt19937;
use Random\Randomizer;

class CardDeckService
{
    public function __construct(private CardCatalogService $catalog) {}

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('cards.timezone'))->startOfDay();
    }

    public function nextResetAt(): CarbonImmutable
    {
        return $this->today()->addDay()->utc();
    }

    public function drawnToday(User $user): ?CardDraw
    {
        return CardDraw::query()
            ->where('user_id', $user->id)
            ->where('draw_date', $this->today()->toDateString())
            ->first();
    }

    public function offersFor(User $user, ?CarbonImmutable $day = null): array
    {
        $day ??= $this->today();
        $pool = $this->catalog->poolFor($user);

        if ($pool === []) {
            return [];
        }

        $seed = crc32($user->id.'|'.config('app.key'));
        $randomizer = new Randomizer(new Mt19937($seed));
        $randomizer->shuffleArray($pool);

        $offset = intdiv($day->startOfDay()->getTimestamp(), 86400) % count($pool);
        $pool = array_merge(array_slice($pool, $offset), array_slice($pool, 0, $offset));

        return array_slice($pool, 0, min((int) config('cards.daily_balls'), count($pool)));
    }

    public function draw(User $user, int $ballIndex): array
    {
        $day = $this->today();
        $offers = $this->offersFor($user, $day);

        if ($offers === []) {
            throw ValidationException::withMessages(['ball' => __('ui.cards_empty')]);
        }

        if ($ballIndex < 0 || $ballIndex >= count($offers)) {
            throw ValidationException::withMessages(['ball' => __('ui.cards_invalid_ball')]);
        }

        try {
            return DB::transaction(function () use ($user, $day, $offers, $ballIndex) {
                $exists = CardDraw::query()
                    ->where('user_id', $user->id)
                    ->where('draw_date', $day->toDateString())
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
                }

                $cardKey = $offers[$ballIndex];
                $userCard = UserCard::query()
                    ->where('user_id', $user->id)
                    ->where('card_key', $cardKey)
                    ->lockForUpdate()
                    ->first();

                $duplicate = $userCard !== null;
                $inkEarned = $duplicate ? (int) config('cards.ink_per_duplicate') : 0;

                CardDraw::create([
                    'user_id' => $user->id,
                    'draw_date' => $day->toDateString(),
                    'card_key' => $cardKey,
                    'ball_index' => $ballIndex,
                    'duplicate' => $duplicate,
                    'ink_earned' => $inkEarned,
                ]);

                if ($userCard) {
                    $userCard->increment('copies');
                } else {
                    $userCard = UserCard::create([
                        'user_id' => $user->id,
                        'card_key' => $cardKey,
                        'copies' => 1,
                        'first_unlocked_at' => now(),
                    ]);
                }

                $wallet = UserWallet::firstOrCreate(['user_id' => $user->id]);

                if ($inkEarned > 0) {
                    $wallet->increment('ink', $inkEarned);
                }

                return [
                    'card' => $this->catalog->metadata($cardKey),
                    'duplicate' => $duplicate,
                    'copies' => (int) $userCard->fresh()->copies,
                    'ink' => (int) $wallet->fresh()->ink,
                    'inkEarned' => $inkEarned,
                    'resetAt' => $this->nextResetAt()->toIso8601String(),
                ];
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
            }

            throw $exception;
        }
    }
}
```

- [ ] **Step 4: Ejecutar y ver el verde**

```bash
cmd.exe /C 'php artisan test --filter=CardDrawTest'
```

- [ ] **Step 5: Pint**

```bash
cmd.exe /C 'vendor\bin\pint app\Services\CardDeckService.php tests\Feature\CardDrawTest.php'
```

---

## Task 4: Controladores y rutas

**Files:**
- Create: `app/Http/Controllers/CardController.php`
- Create: `app/Http/Controllers/CardDrawController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/CardPageTest.php`

- [ ] **Step 1: Escribir la prueba de página que falla**

```php
<?php

use App\Models\Book;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserWallet;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de sangre',
        'author' => 'Rebecca Yarros',
    ]);
});

it('carga la página de cartas con el pool y las balotas', function () {
    $this->get(route('cards.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cards/Index')
            ->has('cards', 9)
            ->has('offers', 8)
            ->where('progress.total', 9)
            ->where('progress.unlocked', 0)
            ->where('drawn', null)
            ->where('ink', 0)
        );
});

it('no expone la clave ni el personaje en las balotas', function () {
    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('offers.0.key')
            ->missing('offers.0.character')
            ->missing('offers.0.emblem')
        );
});

it('devuelve la carta desbloqueada en el idioma activo', function () {
    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => 'violet',
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);

    $this->getJson(route('cards.show', 'violet'))
        ->assertOk()
        ->assertJsonPath('card.key', 'violet')
        ->assertJsonPath('card.character', 'Violet Sorrengail')
        ->assertJsonPath('copies', 1);
});

it('prohíbe abrir una carta que no está desbloqueada', function () {
    $this->getJson(route('cards.show', 'violet'))->assertForbidden();
});

it('devuelve 404 para una carta desconocida', function () {
    $this->getJson(route('cards.show', 'no_existe'))->assertNotFound();
});

it('tira una vez al día y la segunda responde 422', function () {
    $this->postJson(route('cards.draw'), ['ball' => 0])
        ->assertOk()
        ->assertJsonStructure(['card', 'duplicate', 'copies', 'ink', 'inkEarned', 'resetAt']);

    $this->postJson(route('cards.draw'), ['ball' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('ball');
});

it('guarda el resultado y la tinta de un repetido', function () {
    $offers = app(App\Services\CardDeckService::class)->offersFor($this->user);

    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => $offers[0],
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);
    UserWallet::create(['user_id' => $this->user->id, 'ink' => 0]);

    $this->postJson(route('cards.draw'), ['ball' => 0])
        ->assertOk()
        ->assertJsonPath('duplicate', true)
        ->assertJsonPath('copies', 2)
        ->assertJsonPath('ink', 1);
});

it('no mezcla datos de otras usuarias', function () {
    $other = User::factory()->create();
    Book::factory()->for($other)->create([
        'title' => 'El mapa de los anhelos',
        'author' => 'Alice Kellen',
    ]);
    UserCard::create([
        'user_id' => $other->id,
        'card_key' => 'grace',
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cards', 9)
            ->where('progress.unlocked', 0)
        );

    $this->getJson(route('cards.show', 'grace'))->assertForbidden();
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

```bash
cmd.exe /C 'php artisan test --filter=CardPageTest'
```

- [ ] **Step 3: Crear los controladores**

`app/Http/Controllers/CardController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\CardCatalogService;
use App\Services\CardDeckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    public function index(
        Request $request,
        CardCatalogService $catalog,
        CardDeckService $deck,
    ): Response {
        $user = $request->user();
        $pool = $catalog->poolFor($user);
        $owned = $user->userCards()->whereIn('card_key', $pool)->get()->keyBy('card_key');
        $drawn = $deck->drawnToday($user);

        $cards = collect($pool)->map(function (string $key) use ($catalog, $owned) {
            $card = $owned->get($key);

            return $catalog->metadata($key) + [
                'unlocked' => $card !== null,
                'copies' => (int) ($card->copies ?? 0),
            ];
        })->values();

        $offers = collect($deck->offersFor($user))->values()->map(function (string $key, int $index) use ($catalog) {
            $meta = $catalog->metadata($key);

            return [
                'index' => $index,
                'family' => $meta['family'],
                'familyLabel' => $meta['familyLabel'],
                'familyEmblem' => $meta['familyEmblem'],
            ];
        });

        return Inertia::render('Cards/Index', [
            'cards' => $cards,
            'offers' => $offers,
            'drawn' => $drawn ? [
                'card' => $catalog->metadata($drawn->card_key),
                'duplicate' => $drawn->duplicate,
                'copies' => (int) ($owned->get($drawn->card_key)?->copies ?? 0),
                'inkEarned' => (int) $drawn->ink_earned,
            ] : null,
            'ink' => (int) ($user->wallet?->ink ?? 0),
            'progress' => [
                'unlocked' => $owned->count(),
                'total' => count($pool),
            ],
            'resetAt' => $deck->nextResetAt()->toIso8601String(),
            'dailyBalls' => (int) config('cards.daily_balls'),
        ]);
    }

    public function show(Request $request, string $card, CardCatalogService $catalog): JsonResponse
    {
        abort_unless($catalog->exists($card), 404);

        $user = $request->user();

        abort_unless($catalog->inPool($user, $card), 403);

        $owned = $user->userCards()->where('card_key', $card)->first();

        abort_if($owned === null, 403);

        $letter = $catalog->letter($card, app()->getLocale());

        abort_if($letter === null, 404);

        return response()->json([
            'card' => $catalog->metadata($card) + ['letter' => $letter],
            'duplicate' => $owned->copies > 1,
            'copies' => (int) $owned->copies,
        ]);
    }
}
```

`app/Http/Controllers/CardDrawController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\CardDeckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardDrawController extends Controller
{
    public function store(Request $request, CardDeckService $deck): JsonResponse
    {
        $data = $request->validate([
            'ball' => ['required', 'integer', 'min:0'],
        ]);

        return response()->json($deck->draw($request->user(), $data['ball']));
    }
}
```

- [ ] **Step 4: Añadir las rutas**

En `routes/web.php`, dentro del grupo `auth` + `nickname`:

```php
Route::get('/cards', [CardController::class, 'index'])->name('cards.index');
Route::get('/cards/{card}', [CardController::class, 'show'])
    ->where('card', '[a-z_]+')
    ->name('cards.show');
Route::post('/cards/draw', [CardDrawController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('cards.draw');
```

Importar `CardController` y `CardDrawController`.

- [ ] **Step 5: Ejecutar y ver el verde**

```bash
cmd.exe /C 'php artisan test --filter=CardPageTest'
cmd.exe /C 'php artisan test --filter=CardDrawTest'
```

- [ ] **Step 6: Pint**

```bash
cmd.exe /C 'vendor\bin\pint app\Http\Controllers routes\web.php tests\Feature\CardPageTest.php'
```

---

## Task 5: Textos de interfaz en los 8 idiomas

**Files:**
- Modify: `lang/{es,en,pt,fr,de,it,ca,gl}.json`

- [ ] **Step 1: Añadir las 30 claves a `lang/es.json`**

Valores fuente:

```json
{
    "ui.nav_cards": "Cartas",
    "ui.cards_title": "Cartas",
    "ui.cards_subtitle": "Un bombo de balotas y cartas de personajes que esperan en tu biblioteca.",
    "ui.cards_ink": "Tinta",
    "ui.cards_ink_help": "Cada carta repetida se convierte en 1 de tinta. La tinta servirá para los eventos.",
    "ui.cards_choose_ball": "Elige una balota",
    "ui.cards_ball_label": "Balota :number, :family",
    "ui.cards_crank": "Girar la manivela",
    "ui.cards_cranking": "Girando…",
    "ui.cards_open_letter": "Abrir la carta",
    "ui.cards_new_card": "Carta nueva",
    "ui.cards_duplicate": "Ya tenías esta carta: +1 tinta",
    "ui.cards_album": "Álbum",
    "ui.cards_album_progress": ":unlocked de :total cartas",
    "ui.cards_locked": "Aún no la tienes",
    "ui.cards_locked_hint": "Sigue sacando balotas para desbloquearla.",
    "ui.cards_copies": "×:count",
    "ui.cards_empty": "Aún no hay cartas para tus libros.",
    "ui.cards_empty_help": "Añade un libro con personajes conocidos para empezar la colección.",
    "ui.cards_already_drawn": "Ya has sacado tu carta de hoy. Vuelve mañana.",
    "ui.cards_invalid_ball": "Esa balota ya no está en el bombo.",
    "ui.cards_reset_in": "Vuelve en :time",
    "ui.cards_hours_minutes": ":hours h :minutes min",
    "ui.cards_hours": ":hours h",
    "ui.cards_minutes": ":minutes min",
    "ui.cards_letter_from": "Carta de :character",
    "ui.cards_close": "Cerrar",
    "ui.cards_draw_error": "No se pudo girar el bombo. Inténtalo de nuevo.",
    "ui.cards_letter_error": "No se pudo abrir la carta. Inténtalo de nuevo.",
    "ui.cards_disclaimer": "Cartas inspiradas en personajes de ficción. Uso personal."
}
```

- [ ] **Step 2: Traducir las mismas claves a los otros 7 idiomas**

Mantener el tono de la interfaz (tratamiento cercano, frases cortas). No dejar ninguna clave sin traducir.

- [ ] **Step 3: Ejecutar el catálogo**

```bash
cmd.exe /C 'php artisan test --filter=CatalogTest'
```

Esperado: verde; si falta una clave en un idioma, el test dice cuál.

---

## Task 6: Navegación

**Files:**
- Modify: `resources/js/Components/AppShell.vue`
- Modify: `resources/js/Components/__tests__/AppShell.test.js`

- [ ] **Step 1: Actualizar la prueba de navegación**

En `AppShell.test.js`, cambiar las expectativas:

```js
expect(links.map((link) => link.attributes('href'))).toEqual([
    '/',
    '/shelves',
    '/cards',
    '/decorations',
    '/settings',
]);

// ...

expect(
    nav.findAll('[data-nav-menu] a').map((link) => link.attributes('href'))
).toEqual(['/cards', '/decorations', '/settings']);
```

- [ ] **Step 2: Añadir el enlace**

En `AppShell.vue`:

```js
const links = computed(() => [
    { label: t('ui.nav_library'), href: '/' },
    { label: t('ui.nav_shelves'), href: '/shelves' },
    { label: t('ui.nav_cards'), href: '/cards' },
    { label: t('ui.nav_decorations'), href: '/decorations' },
    { label: t('ui.nav_settings'), href: '/settings' },
]);
```

`primaryLinks` sigue tomando los dos primeros y `menuLinks` el resto.

- [ ] **Step 3: Ejecutar la prueba**

```bash
cmd.exe /C 'npm run test -- --run resources/js/Components/__tests__/AppShell.test.js'
```

---

## Task 7: Emblemas y clases visuales

**Files:**
- Create: `resources/js/Components/CardEmblem.vue`
- Modify: `resources/css/textures.css`
- Test: `resources/js/Components/__tests__/CardEmblem.test.js`

- [ ] **Step 1: Escribir la prueba del emblema**

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CardEmblem from '../CardEmblem.vue';

describe('CardEmblem', () => {
    it('dibuja un svg para cada emblema del catálogo', () => {
        const names = [
            'dragon', 'shadow', 'wing', 'feather', 'shield', 'sword', 'compass',
            'flame', 'knot', 'map', 'lantern', 'star', 'moon', 'chess', 'cat',
            'music', 'mask', 'crown', 'key', 'rose',
        ];

        for (const name of names) {
            const wrapper = mount(CardEmblem, { props: { name } });
            expect(wrapper.find('svg').exists()).toBe(true);
            expect(wrapper.findAll('path').length).toBeGreaterThan(0);
        }
    });

    it('cae a la estrella si el emblema no existe', () => {
        const wrapper = mount(CardEmblem, { props: { name: 'desconocido' } });

        expect(wrapper.find('svg').exists()).toBe(true);
    });
});
```

- [ ] **Step 2: Crear `CardEmblem.vue`**

Un `svg` de 24×24, `fill="none"`, `stroke="currentColor"`, `stroke-width="1.4"`, con un mapa de 20 emblemas y caída a `star`. Cada emblema son 1–3 `<path>` sencillos. El componente acepta `name: String` y `size: Number` (por defecto 24) y aplica `aria-hidden="true"`.

- [ ] **Step 3: Añadir las clases a `textures.css`**

Al final del archivo, con los tokens existentes:

- `.card-machine` — base de madera con marco de latón.
- `.card-machine__base` — terciopelo `night`/`panel`.
- `.card-machine__dome` — cúpula de cristal con borde `dorado` y brillo radial.
- `.card-machine__ball` — balota `pergamino` con sombra y emblema `madera`.
- `.card-machine__ball--selected` — elevación y anillo `gold`.
- `.card-machine__ball--drop` — animación de caída.
- `.card-machine__crank` — manivela de latón.
- `.card-machine__crank--turning` — giro de 360°.
- `.card-machine__chute` — rampa de latón.
- `.card-envelope` — sobre `crema` con solapa.
- `.card-envelope__flap` — solapa triangular.
- `.card-envelope__seal` — lacre `danger-strong` con emblema `pergamino`.
- `.card-envelope__label` — texto de apertura.
- `.card-letter` — hoja `pergamino` con borde `dorado` y textura sutil.
- `.card-letter__body` — texto `tinta`, `font-serif`, interlineado amplio.
- `.card-letter__dropcap` — capitular en el primer párrafo.
- `.card-album` — gabinete de madera.
- `.card-slot` — ranura del álbum.
- `.card-back` — reverso geométrico de carta bloqueada.
- `.card-monogram` — monograma centrado.
- `.card-ink` — frasco de tinta.

Incluir `@media (prefers-reduced-motion: reduce)` para desactivar las animaciones de balota, manivela, sobre y carta.

- [ ] **Step 4: Ejecutar la prueba**

```bash
cmd.exe /C 'npm run test -- --run resources/js/Components/__tests__/CardEmblem.test.js'
```

---

## Task 8: Bombo y sobre

**Files:**
- Create: `resources/js/Components/CardMachine.vue`
- Create: `resources/js/Components/CardEnvelope.vue`
- Create: `resources/js/cards.js`
- Create: `resources/js/cards.test.js`
- Test: `resources/js/Components/__tests__/CardMachine.test.js`

- [ ] **Step 1: Escribir `cards.test.js`**

```js
import { describe, expect, it } from 'vitest';
import { countdownParts } from './cards';

describe('countdownParts', () => {
    it('separa horas y minutos', () => {
        expect(countdownParts('2026-11-04T05:00:00Z', new Date('2026-11-03T20:00:00Z')))
            .toEqual({ hours: 9, minutes: 0 });
    });

    it('devuelve solo minutos si falta menos de una hora', () => {
        expect(countdownParts('2026-11-04T05:00:00Z', new Date('2026-11-04T04:30:00Z')))
            .toEqual({ hours: 0, minutes: 30 });
    });

    it('redondea hacia arriba al minuto siguiente', () => {
        expect(countdownParts('2026-11-04T05:00:00Z', new Date('2026-11-04T04:59:30Z')))
            .toEqual({ hours: 0, minutes: 1 });
    });

    it('devuelve cero si ya pasó', () => {
        expect(countdownParts('2026-11-04T05:00:00Z', new Date('2026-11-04T06:00:00Z')))
            .toEqual({ hours: 0, minutes: 0 });
    });
});
```

- [ ] **Step 2: Crear `resources/js/cards.js`**

```js
export function countdownParts(resetAt, now = new Date()) {
    const remaining = new Date(resetAt).getTime() - now.getTime();

    if (remaining <= 0) {
        return { hours: 0, minutes: 0 };
    }

    const totalMinutes = Math.ceil(remaining / 60000);

    return {
        hours: Math.floor(totalMinutes / 60),
        minutes: totalMinutes % 60,
    };
}
```

- [ ] **Step 3: Escribir la prueba de `CardMachine`**

Montar con `offers` de 3 balotas, `selected: null`, `drawing: false`, `result: null`, `resetAt` futuro. Comprobar:

- Se renderizan 3 botones de balota con `aria-label`.
- La manivela está desactivada sin selección.
- Al pulsar una balota se emite `select` con el índice.
- Con `selected: 0`, la manivela se activa y al pulsarla emite `crank`.
- Con `result`, aparece el sobre y al pulsarlo emite `open`.
- Con `result.duplicate`, aparece el texto de repetida.
- Con `drawing`, la manivela tiene la clase de giro y está desactivada.

- [ ] **Step 4: Crear `CardMachine.vue`**

Props: `offers`, `selected`, `drawing`, `result`, `resetAt`, `dailyBalls`. Emits: `select`, `crank`, `open`.

- Cúpula con `v-for="offer in offers"`.
- Cada balota: `<button type="button" class="card-machine__ball" :class="{ 'card-machine__ball--selected': selected === offer.index, 'card-machine__ball--drop': result && selected === offer.index }" :disabled="drawing || !!result" :aria-pressed="selected === offer.index" :aria-label="t('ui.cards_ball_label', { number: offer.index + 1, family: offer.familyLabel })" @click="$emit('select', offer.index)">` con `<CardEmblem :name="offer.familyEmblem" />`.
- Manivela: `<button type="button" class="card-machine__crank" :class="{ 'card-machine__crank--turning': drawing }" :disabled="selected === null || drawing || !!result" :aria-label="t('ui.cards_crank')" @click="$emit('crank')">`.
- Texto `ui.cards_choose_ball` si no hay selección y no hay resultado.
- Texto `ui.cards_cranking` si `drawing`.
- `<CardEnvelope v-if="result" :card="result.card" :duplicate="result.duplicate" @open="$emit('open')" />`.
- Cuenta atrás: `const now = ref(new Date())`; `const countdown = computed(() => props.result ? countdownParts(props.resetAt, now.value) : null)`; `countdownText` usa `t('ui.cards_hours_minutes')`, `t('ui.cards_hours')` o `t('ui.cards_minutes')`; se muestra con `t('ui.cards_reset_in', { time: countdownText(countdown) })`; `onMounted` actualiza `now` cada 60 s; `onBeforeUnmount` limpia.
- Región `aria-live="polite"` para el resultado.

- [ ] **Step 5: Crear `CardEnvelope.vue`**

Props: `card`, `duplicate`. Emits: `open`.

- `<button type="button" class="card-envelope" :aria-label="t('ui.cards_open_letter')" @click="$emit('open')">`.
- Solapa, lacre con `<CardEmblem :name="card.familyEmblem" />`; el monograma se
  reserva para la carta y el álbum.
- Si `duplicate`, `<p class="text-sm text-danger">{{ t('ui.cards_duplicate') }}</p>`.

- [ ] **Step 6: Ejecutar las pruebas**

```bash
cmd.exe /C 'npm run test -- --run resources/js/cards.test.js resources/js/Components/__tests__/CardMachine.test.js'
```

---

## Task 9: Carta en pergamino

**Files:**
- Create: `resources/js/Components/CardLetterModal.vue`
- Test: `resources/js/Components/__tests__/CardLetterModal.test.js`

- [ ] **Step 1: Escribir la prueba**

Montar con `show: true`, `letter` con `card.letter` de tres párrafos, `copies: 2`. Comprobar:

- Se renderizan 3 `<p>`.
- El primero tiene `card-letter__dropcap`.
- Se ve el personaje y la familia.
- Se ve `×2`.
- El botón de cerrar emite `close`.
- Sin `letter`, no se renderiza el artículo.

- [ ] **Step 2: Crear el componente**

- Usa `Modal` con `max-width="2xl"` y `@close="$emit('close')"`.
- `paragraphs = computed(() => (props.letter?.card?.letter ?? '').split(/\n{2,}/).map((p) => p.trim()).filter(Boolean))`.
- Cabecera: `<CardEmblem :name="letter.card.emblem" />`, `<span class="card-monogram">{{ letter.card.monogram }}</span>`, `t('ui.cards_letter_from', { character: letter.card.character })`, `letter.card.familyLabel`, botón `ui.cards_close`.
- Cuerpo: `<p v-for="(paragraph, index) in paragraphs" :key="index" :class="{ 'card-letter__dropcap': index === 0 }">{{ paragraph }}</p>`.
- Pie: `ui.cards_new_card` o `ui.cards_duplicate` + `ui.cards_copies` si `copies > 1`.

- [ ] **Step 3: Ejecutar la prueba**

```bash
cmd.exe /C 'npm run test -- --run resources/js/Components/__tests__/CardLetterModal.test.js'
```

---

## Task 10: Álbum y página

**Files:**
- Create: `resources/js/Components/CardAlbum.vue`
- Create: `resources/js/Pages/Cards/Index.vue`
- Test: `resources/js/Components/__tests__/CardAlbum.test.js`

- [ ] **Step 1: Escribir la prueba del álbum**

Montar con 3 cartas (2 desbloqueadas, 1 bloqueada), `progress: { unlocked: 2, total: 3 }`. Comprobar:

- Hay 3 ranuras.
- Las bloqueadas muestran `?` y no son botones.
- Las desbloqueadas son botones y emiten `open` con la clave.
- Se ve `2 de 3 cartas`.
- Una carta con `copies: 2` muestra `×2`.

- [ ] **Step 2: Crear `CardAlbum.vue`**

Props: `cards`, `progress`. Emits: `open`.

- Cabecera: `ui.cards_album`, barra `gold` con `progress.unlocked / progress.total`, texto `ui.cards_album_progress`.
- Rejilla `grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5`.
- Desbloqueada: `<button class="card-slot" @click="$emit('open', card.key)">` con `card.emblem`, monograma, personaje, familia y badge `×N`.
- Bloqueada: `<div class="card-slot card-back" aria-disabled="true">` con `card.familyEmblem`, `?`, `ui.cards_locked`, `title` = `ui.cards_locked_hint`.
- Pie: `ui.cards_disclaimer`.

- [ ] **Step 3: Crear `Pages/Cards/Index.vue`**

```vue
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardAlbum from '@/Components/CardAlbum.vue';
import CardLetterModal from '@/Components/CardLetterModal.vue';
import CardMachine from '@/Components/CardMachine.vue';
import { useI18n } from '@/i18n';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps({
    cards: { type: Array, required: true },
    offers: { type: Array, required: true },
    drawn: { type: Object, default: null },
    ink: { type: Number, default: 0 },
    progress: { type: Object, required: true },
    resetAt: { type: String, required: true },
    dailyBalls: { type: Number, default: 8 },
});

const { t } = useI18n();
const cards = ref(props.cards);
const ink = ref(props.ink);
const selected = ref(null);
const drawing = ref(false);
const result = ref(props.drawn);
const letter = ref(null);
const letterOpen = ref(false);
const error = ref('');

function select(index) {
    if (!result.value && !drawing.value) {
        selected.value = index;
        error.value = '';
    }
}

async function crank() {
    if (selected.value === null || drawing.value || result.value) {
        return;
    }

    drawing.value = true;
    error.value = '';

    try {
        const { data } = await axios.post('/cards/draw', { ball: selected.value });
        result.value = data;
        ink.value = data.ink;

        const card = cards.value.find((item) => item.key === data.card.key);

        if (card) {
            card.unlocked = true;
            card.copies = data.copies;
        }
    } catch (exception) {
        error.value = exception.response?.data?.message || t('ui.cards_draw_error');
    } finally {
        drawing.value = false;
    }
}

async function openLetter(key) {
    error.value = '';

    try {
        const { data } = await axios.get(`/cards/${key}`);
        letter.value = data;
        letterOpen.value = true;
    } catch {
        error.value = t('ui.cards_letter_error');
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-serif text-3xl text-gold">{{ t('ui.cards_title') }}</h1>
                <p class="mt-1 max-w-2xl text-mist">{{ t('ui.cards_subtitle') }}</p>
            </div>
            <div class="card-ink" :title="t('ui.cards_ink_help')">
                <span class="text-sm text-mist">{{ t('ui.cards_ink') }}</span>
                <strong class="text-2xl text-gold">{{ ink }}</strong>
                <span class="sr-only">{{ t('ui.cards_ink_help') }}</span>
            </div>
        </header>

        <p v-if="error" class="mb-4 rounded-control border border-danger/40 bg-danger/10 px-3 py-2 text-danger" role="alert">
            {{ error }}
        </p>

        <div v-if="progress.total === 0" class="rounded-card border border-line bg-panel p-6 text-center">
            <p class="text-moon">{{ t('ui.cards_empty') }}</p>
            <p class="mt-2 text-mist">{{ t('ui.cards_empty_help') }}</p>
            <Link href="/books/create" class="mt-4 inline-block rounded-control bg-arcane px-4 py-2 text-moon">
                {{ t('ui.add_book') }}
            </Link>
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-2">
            <CardMachine
                :offers="offers"
                :selected="selected"
                :drawing="drawing"
                :result="result"
                :reset-at="resetAt"
                :daily-balls="dailyBalls"
                @select="select"
                @crank="crank"
                @open="openLetter(result.card.key)"
            />
            <CardAlbum :cards="cards" :progress="progress" @open="openLetter" />
        </div>

        <CardLetterModal
            :show="letterOpen"
            :letter="letter"
            @close="letterOpen = false"
        />
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 4: Ejecutar las pruebas**

```bash
cmd.exe /C 'npm run test -- --run resources/js/Components/__tests__/CardAlbum.test.js'
```

- [ ] **Step 5: Compilar**

```bash
cmd.exe /C 'npm run build'
```

---

## Task 11: Contenido — lote 1 (Empíreo I)

> **Nota (2026-11-04, decisión de la usuaria):** la fase 1 entrega las cartas
> solo en español (`es`). Las traducciones a los otros 7 idiomas se harán por
> lotes más adelante; mientras tanto, `letter()` sirve `es` como respaldo y
> `CardContentTest` exige únicamente `es`. Ese mismo día la usuaria pidió
> cartas más cortas (unos 120 palabras, rango 120–160) y cabecera del modal
> solo con «Carta de {personaje}», sin emblema, monograma ni familia.

**Files (40):**
- `resources/cards/violet/{es,en,pt,fr,de,it,ca,gl}.md`
- `resources/cards/xaden/{...}.md`
- `resources/cards/tairn/{...}.md`
- `resources/cards/andarna/{...}.md`
- `resources/cards/rhiannon/{...}.md`

- [ ] **Step 1: Escribir las cartas**

Cada archivo: 250+ palabras, primera persona, saludo, cuerpo, firma y posdata
opcional. Sin citas literales ni HTML.

Briefs:

| Carta | Voz y tema |
|---|---|
| `violet` | Estratega, feroz, protectora. Habla del cuadrante, de sus amigos y de elegir el riesgo. Trata a quien lee como cadete. |
| `xaden` | Reservado, intenso, leal. Sombras y confianza; advierte de no entregarla a cualquiera. |
| `tairn` | Dragón antiguo, gruñón, posesivo. Llama «humana» a quien lee; habla del vínculo y del cielo. |
| `andarna` | Dragón joven, curioso, juguetón. Habla de crecer, de su color y de encontrar su propio lugar. |
| `rhiannon` | Cálida, leal, práctica. El escuadrón, la confianza y los escudos que se levantan entre amigos. |

- [ ] **Step 2: Comprobar archivos y palabras**

Revisar a mano que haya 8 archivos por carta y ninguno por debajo de 250
palabras. La prueba automática se añade en la tarea 15.

- [ ] **Step 3: Revisar acentos y codificación**

Abrir al menos un archivo de cada idioma y confirmar UTF-8 y tildes correctas.

---

## Task 12: Contenido — lote 2 (Empíreo II + mapa)

**Files (40):**
- `resources/cards/liam/{...}.md`
- `resources/cards/dain/{...}.md`
- `resources/cards/imogen/{...}.md`
- `resources/cards/bodhi/{...}.md`
- `resources/cards/grace/{...}.md`

Briefs:

| Carta | Voz y tema |
|---|---|
| `liam` | Sereno, valiente, protector. El honor, los suyos y la promesa de cuidar a quien queda. |
| `dain` | Dividido entre las normas y el corazón. Arrepentimiento, deber y lo que no se atrevió a decir. |
| `imogen` | Directa, feroz, protectora. La fuerza, la memoria y las personas que no se abandonan. |
| `bodhi` | Firme, leal, con humor seco. La familia elegida y el peso del apellido. |
| `grace` | Introspectiva, doliente, luminosa. El mapa de los anhelos, la ausencia y los caminos que quedan. |

- [ ] **Step 1: Escribir las 40 cartas.**
- [ ] **Step 2: Revisar 8 archivos por carta y 250+ palabras.**
- [ ] **Step 3: Revisar codificación.**

---

## Task 13: Contenido — lote 3 (mapa + biblioteca I)

**Files (40):**
- `resources/cards/will/{...}.md`
- `resources/cards/lucy/{...}.md`
- `resources/cards/nora/{...}.md`
- `resources/cards/mrs_elm/{...}.md`
- `resources/cards/voltaire/{...}.md`

Briefs:

| Carta | Voz y tema |
|---|---|
| `will` | Misterioso, cálido, poético. Los caminos que se eligen y el mapa que no muestra todo. |
| `lucy` | Imaginativa, juguetona, sabia. El mapa que dibujó, las estrellas y los deseos pequeños. |
| `nora` | Reflexiva, librera, honesta. Los arrepentimientos, las segundas oportunidades y las vidas posibles. |
| `mrs_elm` | Sabia, serena, enigmática. El ajedrez, las decisiones y la biblioteca que existe a medianoche. |
| `voltaire` | Voz felina, altiva y cariñosa. Las ventanas, las siestas y las personas que valen la pena. |

- [ ] **Step 1: Escribir las 40 cartas.**
- [ ] **Step 2: Revisar 8 archivos por carta y 250+ palabras.**
- [ ] **Step 3: Revisar codificación.**

---

## Task 14: Contenido — lote 4 (biblioteca II + perfectos)

**Files (40):**
- `resources/cards/ash/{...}.md`
- `resources/cards/jude/{...}.md`
- `resources/cards/aegan/{...}.md`
- `resources/cards/adrik/{...}.md`
- `resources/cards/alex/{...}.md`

Briefs:

| Carta | Voz y tema |
|---|---|
| `ash` | Músico, nostálgico, amable. Las canciones, los caminos no tomados y lo que todavía se puede tocar. |
| `jude` | Decidida, herida, afilada. Las máscaras, la verdad y el precio de entrar en el círculo de los Cash. |
| `aegan` | Controlado, encantador, peligroso. El poder, el juego y lo que se esconde tras la sonrisa. |
| `adrik` | Enigmático, intenso, observador. El silencio, la lealtad y lo que no necesita decirse. |
| `alex` | Rebelde, impulsivo, cálido. La libertad, el apellido y las ganas de romper las reglas. |

- [ ] **Step 1: Escribir las 40 cartas.**
- [ ] **Step 2: Revisar 8 archivos por carta y 250+ palabras.**
- [ ] **Step 3: Revisar codificación.**

---

## Task 15: Prueba de contenido completo

**Files:**
- Create: `tests/Feature/CardContentTest.php`

- [ ] **Step 1: Escribir la prueba**

```php
<?php

use App\Services\CardCatalogService;

it('tiene las 20 cartas en los 8 idiomas con al menos 250 palabras', function () {
    $keys = array_keys(config('cards.cards'));
    $locales = config('languages.curated');

    foreach ($keys as $key) {
        foreach ($locales as $locale) {
            $path = resource_path("cards/{$key}/{$locale}.md");

            expect(is_file($path))->toBeTrue("Falta {$key}/{$locale}.md");

            $text = trim((string) file_get_contents($path));
            $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

            expect(count($words))->toBeGreaterThanOrEqual(250, "{$key}/{$locale}.md tiene pocas palabras");
            expect($text)->not->toContain('<script');
            expect($text)->not->toContain('<a ');
        }
    }
});

it('no deja cartas sin metadatos ni emblemas', function () {
    $catalog = app(CardCatalogService::class);

    foreach (array_keys(config('cards.cards')) as $key) {
        $meta = $catalog->metadata($key);

        expect($catalog->exists($key))->toBeTrue();
        expect($catalog->letter($key, 'es'))->not->toBeNull();
        expect($meta['familyEmblem'])->not->toBe('');
        expect($meta['emblem'])->not->toBe('');
    }
});
```

- [ ] **Step 2: Ejecutar**

```bash
cmd.exe /C 'php artisan test --filter=CardContentTest'
```

Esperado: verde con las 20 cartas en español (fase 1). Cuando se completen las
traducciones, la prueba se ampliará a los 8 idiomas según la spec.

- [ ] **Step 3: Pint**

```bash
cmd.exe /C 'vendor\bin\pint tests\Feature\CardContentTest.php'
```

---

## Task 16: Documentación visual y verificación final

**Files:**
- Modify: `docs/DISENO.md`

- [ ] **Step 1: Actualizar `docs/DISENO.md`**

- Cambiar `**Última actualización:**` a `2026-11-03`.
- Añadir la sección **Cartas** después de **Estanterías**:
  - Navegación: quinto destino, orden y comportamiento móvil.
  - Bombo: cúpula, balotas, manivela, rampa y estados.
  - Sobre: papel, lacre, monograma y emblema.
  - Carta: pergamino, tipografía, capitular y modal.
  - Álbum: gabinete, ranuras, bloqueadas/desbloqueadas, copias y progreso.
  - Tinta: frasco, contador y ayuda.
  - Responsive: 360 px, 768 px y 1280 px.
  - Accesibilidad: foco, etiquetas, `aria-live`, `prefers-reduced-motion`.
  - Clases nuevas de `textures.css` y tokens usados.
- Añadir la fila del registro de cambios con fecha 2026-11-03.

- [ ] **Step 2: Ejecutar toda la suite**

```bash
cmd.exe /C 'php artisan test'
cmd.exe /C 'npm run test'
cmd.exe /C 'vendor\bin\pint --test'
cmd.exe /C 'npm run build'
```

- [ ] **Step 3: Revisión manual**

- 360 px: bombo, sobre, carta y álbum sin desbordes.
- 768 px y 1280 px: dos columnas y rejilla del álbum.
- Teclado: balotas, manivela, sobre, carta, álbum y Escape.
- Recarga tras la tirada: el bombo sigue inerte y el sobre aparece.
- `prefers-reduced-motion`: sin animaciones.
- Repetida: aviso `+1 tinta` y contador actualizado.

- [ ] **Step 4: Informar a la usuaria**

Listar los archivos modificados y dejar que ella haga el commit. No ejecutar
`git add`, `git commit` ni `git push`.

---

## Orden recomendado de ejecución

1. Tasks 1–4: backend y pruebas (se puede probar con las cartas del primer lote).
2. Task 5: textos de interfaz.
3. Task 6: navegación.
4. Tasks 7–10: frontend y pruebas.
5. Tasks 11–15: contenido por lotes.
6. Task 16: documentación y verificación final.

Las tareas 11–15 se pueden repartir en varias sesiones; el código y las pruebas
de las tareas 1–10 deben quedar verdes antes de empezar el contenido.
