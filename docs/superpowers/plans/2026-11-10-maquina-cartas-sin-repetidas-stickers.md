# Máquina sin cartas repetidas y stickers de biblioteca — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hacer que la máquina de `/cards` no repita cartas, dispense 20 stickers nuevos de objetos de biblioteca y se detenga al completar la colección.

**Architecture:** Las bolitas se calculan en el servidor como una mezcla diaria de cartas no obtenidas y stickers no obtenidos. El frontend solo recibe índices, así que todas las bolitas se ven iguales. Las cartas siguen en `user_cards`; los stickers se guardan como colectables únicos en `user_collectibles` con `event_key = maquina`.

**Tech Stack:** Laravel 13, Pest, Inertia.js, Vue 3, Tailwind CSS 4, Vitest, MySQL/SQLite.

**Regla del proyecto:** la usuaria hace los commits. No ejecutar `git add`, `git commit`, `git push` ni `git tag`. Al final de cada tarea, revisar el diff y avisar a la usuaria; no commitear.

---

## Estructura de archivos

**Crear**

- `database/migrations/2026_11_10_000000_add_kind_to_card_draws_table.php`
- `resources/js/Components/LibrarySticker.vue`
- `resources/js/Components/__tests__/LibrarySticker.test.js`
- `tests/Feature/CardDrawModelTest.php`

**Modificar**

- `app/Models/CardDraw.php`
- `app/Services/CardCatalogService.php`
- `app/Services/CardDeckService.php`
- `app/Http/Controllers/CardController.php`
- `app/Http/Controllers/EventController.php`
- `app/Services/EventService.php`
- `config/cards.php`
- `resources/js/Components/CardMachine.vue`
- `resources/js/Components/CardAlbum.vue`
- `resources/js/Pages/Cards/Index.vue`
- `resources/js/Pages/Cards/Album.vue`
- `resources/js/Components/__tests__/CardMachine.test.js`
- `resources/js/Components/__tests__/CardAlbum.test.js`
- `resources/css/textures.css`
- `lang/es.json`
- `lang/en.json`
- `tests/Feature/CardDrawTest.php`
- `tests/Feature/CardPageTest.php`
- `tests/Feature/CardCatalogTest.php`
- `tests/Feature/EventAlbumTest.php`
- `tests/Feature/EventPageTest.php`
- `docs/DISENO.md`

---

## Task 1: Persistir el tipo de objeto en las tiradas

**Files:**
- Create: `tests/Feature/CardDrawModelTest.php`
- Create: `database/migrations/2026_11_10_000000_add_kind_to_card_draws_table.php`
- Modify: `app/Models/CardDraw.php`
- Modify: `app/Services/CardDeckService.php`
- Modify: `app/Http/Controllers/CardController.php`
- Modify: `tests/Feature/CardDrawTest.php`

- [ ] **Step 1: Escribir la prueba que falla**

Crea `tests/Feature/CardDrawModelTest.php`:

```php
<?php

use App\Models\CardDraw;
use App\Models\User;

it('guarda el tipo y la clave del objeto de cada tirada', function () {
    $user = User::factory()->create();

    $draw = CardDraw::create([
        'user_id' => $user->id,
        'draw_date' => '2026-11-10',
        'kind' => 'sticker',
        'item_key' => 'maquina_libro',
        'ball_index' => 2,
        'duplicate' => false,
        'ink_earned' => 0,
    ]);

    expect($draw->fresh()->kind)->toBe('sticker')
        ->and($draw->fresh()->item_key)->toBe('maquina_libro');
});
```

- [ ] **Step 2: Ejecutar la prueba y comprobar que falla**

Run: `php artisan test --filter=CardDrawModelTest`

Expected: FAIL porque `card_draws` todavía no tiene `kind` ni `item_key` y el modelo no los permite.

- [ ] **Step 3: Crear la migración**

Crea `database/migrations/2026_11_10_000000_add_kind_to_card_draws_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_draws', function (Blueprint $table) {
            $table->renameColumn('card_key', 'item_key');
        });

        Schema::table('card_draws', function (Blueprint $table) {
            $table->string('kind', 16)->default('card')->after('item_key');
        });
    }

    public function down(): void
    {
        Schema::table('card_draws', function (Blueprint $table) {
            $table->dropColumn('kind');
        });

        Schema::table('card_draws', function (Blueprint $table) {
            $table->renameColumn('item_key', 'card_key');
        });
    }
};
```

- [ ] **Step 4: Actualizar el modelo**

En `app/Models/CardDraw.php`, cambia el arreglo `$fillable`:

```php
protected $fillable = ['user_id', 'draw_date', 'kind', 'item_key', 'ball_index', 'duplicate', 'ink_earned'];
```

- [ ] **Step 5: Actualizar las escrituras existentes de `CardDraw`**

En `app/Services/CardDeckService.php`, dentro de `draw()`, cambia `$drawAttributes` para usar las columnas nuevas:

```php
$drawAttributes = [
    'kind' => 'card',
    'item_key' => $cardKey,
    'ball_index' => $ballIndex,
    'duplicate' => $duplicate,
    'ink_earned' => $inkEarned,
];
```

En `app/Http/Controllers/CardController.php`, dentro de `index()`, cambia la lectura del resultado persistido:

```php
'drawn' => $drawn ? [
    'kind' => $drawn->kind,
    'card' => $catalog->metadata($drawn->item_key),
    'sticker' => null,
    'duplicate' => $drawn->duplicate,
    'copies' => (int) ($owned->get($drawn->item_key)?->copies ?? 0),
    'inkEarned' => (int) $drawn->ink_earned,
] : null,
```

En `tests/Feature/CardDrawTest.php`, cambia la primera prueba para usar `item_key` y `kind`:

```php
CardDraw::create([
    'user_id' => $this->user->id,
    'draw_date' => '2026-11-03',
    'kind' => 'card',
    'item_key' => 'violet',
    'ball_index' => 0,
    'duplicate' => false,
    'ink_earned' => 0,
]);
```

- [ ] **Step 6: Ejecutar las pruebas afectadas**

Run: `php artisan test --filter='CardDrawModelTest|CardDrawTest|CardPageTest'`

Expected: PASS. Si falla algún `CardDraw` por `card_key`, cambia solo las escrituras de `CardDraw`; `UserCard` conserva `card_key`.

- [ ] **Step 7: Revisar el diff y avisar**

Run: `git diff --stat`

No commit. Avisa a la usuaria de los archivos tocados.

---

## Task 2: Catálogo de 20 stickers y textos

**Files:**
- Modify: `config/cards.php`
- Modify: `app/Services/CardCatalogService.php`
- Modify: `tests/Feature/CardCatalogTest.php`
- Modify: `lang/es.json`
- Modify: `lang/en.json`

- [ ] **Step 1: Escribir la prueba que falla**

Añade al final de `tests/Feature/CardCatalogTest.php`:

```php
it('expone el catálogo de stickers de la máquina', function () {
    expect($this->catalog->machineStickers())->toHaveCount(20)
        ->and($this->catalog->machineSticker('maquina_libro'))->toMatchArray([
            'key' => 'maquina_libro',
            'name' => 'ui.machine_sticker_libro',
        ])
        ->and($this->catalog->machineStickerMetadata('maquina_libro'))->toBe([
            'key' => 'maquina_libro',
            'name' => 'Libro abierto',
        ])
        ->and($this->catalog->machineSticker('no_existe'))->toBeNull();
});
```

- [ ] **Step 2: Ejecutar la prueba y comprobar que falla**

Run: `php artisan test --filter=CardCatalogTest`

Expected: FAIL porque `machineStickers()` no existe.

- [ ] **Step 3: Añadir el catálogo a `config/cards.php`**

Dentro del arreglo que devuelve `config/cards.php`, después de `'cards' => [...]`, añade:

```php
'machine_stickers' => [
    ['key' => 'maquina_libro', 'name' => 'ui.machine_sticker_libro'],
    ['key' => 'maquina_pluma', 'name' => 'ui.machine_sticker_pluma'],
    ['key' => 'maquina_tintero', 'name' => 'ui.machine_sticker_tintero'],
    ['key' => 'maquina_taza', 'name' => 'ui.machine_sticker_taza'],
    ['key' => 'maquina_gato', 'name' => 'ui.machine_sticker_gato'],
    ['key' => 'maquina_luna', 'name' => 'ui.machine_sticker_luna'],
    ['key' => 'maquina_vela', 'name' => 'ui.machine_sticker_vela'],
    ['key' => 'maquina_planta', 'name' => 'ui.machine_sticker_planta'],
    ['key' => 'maquina_lupa', 'name' => 'ui.machine_sticker_lupa'],
    ['key' => 'maquina_llave', 'name' => 'ui.machine_sticker_llave'],
    ['key' => 'maquina_gafas', 'name' => 'ui.machine_sticker_gafas'],
    ['key' => 'maquina_marcapaginas', 'name' => 'ui.machine_sticker_marcapaginas'],
    ['key' => 'maquina_reloj', 'name' => 'ui.machine_sticker_reloj'],
    ['key' => 'maquina_buho', 'name' => 'ui.machine_sticker_buho'],
    ['key' => 'maquina_pocion', 'name' => 'ui.machine_sticker_pocion'],
    ['key' => 'maquina_pergamino', 'name' => 'ui.machine_sticker_pergamino'],
    ['key' => 'maquina_maquina_escribir', 'name' => 'ui.machine_sticker_maquina_escribir'],
    ['key' => 'maquina_rosa', 'name' => 'ui.machine_sticker_rosa'],
    ['key' => 'maquina_cuervo', 'name' => 'ui.machine_sticker_cuervo'],
    ['key' => 'maquina_cristal', 'name' => 'ui.machine_sticker_cristal'],
],
```

- [ ] **Step 4: Añadir los métodos al catálogo**

En `app/Services/CardCatalogService.php`, después de `cards()`:

```php
public function machineStickers(): array
{
    return config('cards.machine_stickers', []);
}

public function machineSticker(string $key): ?array
{
    foreach ($this->machineStickers() as $sticker) {
        if ($sticker['key'] === $key) {
            return $sticker;
        }
    }

    return null;
}

public function machineStickerMetadata(string $key): ?array
{
    $sticker = $this->machineSticker($key);

    if ($sticker === null) {
        return null;
    }

    return [
        'key' => $sticker['key'],
        'name' => __($sticker['name']),
    ];
}
```

- [ ] **Step 5: Añadir los textos en español**

En `lang/es.json`, añade estas claves manteniendo el orden alfabético y el catálogo plano:

```json
"ui.cards_complete": "¡Colección completa!",
"ui.cards_complete_help": "Ya tienes todas las cartas y todos los stickers. Vuelve cuando añadamos más.",
"ui.cards_machine_stickers": "Stickers de la máquina",
"ui.cards_sticker_new": "¡Sticker nuevo!",
"ui.cards_sticker_progress": ":unlocked de :total stickers",
"ui.machine_sticker_buho": "Búho",
"ui.machine_sticker_cristal": "Cristal de luna",
"ui.machine_sticker_cuervo": "Cuervo",
"ui.machine_sticker_gafas": "Gafas",
"ui.machine_sticker_gato": "Gato lector",
"ui.machine_sticker_libro": "Libro abierto",
"ui.machine_sticker_llave": "Llave antigua",
"ui.machine_sticker_luna": "Luna",
"ui.machine_sticker_lupa": "Lupa",
"ui.machine_sticker_maquina_escribir": "Máquina de escribir",
"ui.machine_sticker_marcapaginas": "Marcapáginas",
"ui.machine_sticker_pergamino": "Pergamino",
"ui.machine_sticker_planta": "Planta",
"ui.machine_sticker_pluma": "Pluma de ganso",
"ui.machine_sticker_pocion": "Poción",
"ui.machine_sticker_reloj": "Reloj de bolsillo",
"ui.machine_sticker_rosa": "Rosa",
"ui.machine_sticker_taza": "Taza de té",
"ui.machine_sticker_tintero": "Tintero",
"ui.machine_sticker_vela": "Vela"
```

- [ ] **Step 6: Añadir los textos en inglés**

En `lang/en.json`, añade las mismas claves con estos valores:

```json
"ui.cards_complete": "Collection complete!",
"ui.cards_complete_help": "You have every card and sticker. Come back when we add more.",
"ui.cards_machine_stickers": "Machine stickers",
"ui.cards_sticker_new": "New sticker!",
"ui.cards_sticker_progress": ":unlocked of :total stickers",
"ui.machine_sticker_buho": "Owl",
"ui.machine_sticker_cristal": "Moon crystal",
"ui.machine_sticker_cuervo": "Raven",
"ui.machine_sticker_gafas": "Glasses",
"ui.machine_sticker_gato": "Reading cat",
"ui.machine_sticker_libro": "Open book",
"ui.machine_sticker_llave": "Antique key",
"ui.machine_sticker_luna": "Moon",
"ui.machine_sticker_lupa": "Magnifying glass",
"ui.machine_sticker_maquina_escribir": "Typewriter",
"ui.machine_sticker_marcapaginas": "Bookmark",
"ui.machine_sticker_pergamino": "Scroll",
"ui.machine_sticker_planta": "Plant",
"ui.machine_sticker_pluma": "Goose quill",
"ui.machine_sticker_pocion": "Potion",
"ui.machine_sticker_reloj": "Pocket watch",
"ui.machine_sticker_rosa": "Rose",
"ui.machine_sticker_taza": "Teacup",
"ui.machine_sticker_tintero": "Inkwell",
"ui.machine_sticker_vela": "Candle"
```

- [ ] **Step 7: Ejecutar las pruebas**

Run: `php artisan test --filter='CardCatalogTest|CatalogTest'`

Expected: PASS. `CatalogTest` verifica que los catálogos siguen planos.

- [ ] **Step 8: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 3: Mezcla diaria sin repetidos y tirada de cartas o stickers

**Files:**
- Modify: `app/Services/CardDeckService.php`
- Modify: `tests/Feature/CardDrawTest.php`

- [ ] **Step 1: Reescribir `tests/Feature/CardDrawTest.php` con las nuevas reglas**

Sustituye el contenido completo por:

```php
<?php

use App\Models\Book;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserCollectible;
use App\Services\CardDeckService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config(['events.enabled' => false]);
    $this->user = User::factory()->create();
});

function giveEmpyreanBooks(User $user): void
{
    Book::factory()->for($user)->create([
        'title' => 'Alas de sangre',
        'author' => 'Rebecca Yarros',
    ]);
}

function ownAllCards(User $user): void
{
    foreach (array_keys(config('cards.cards')) as $key) {
        UserCard::create([
            'user_id' => $user->id,
            'card_key' => $key,
            'copies' => 1,
            'first_unlocked_at' => now(),
        ]);
    }
}

function ownAllMachineStickers(User $user): void
{
    foreach (config('cards.machine_stickers') as $sticker) {
        UserCollectible::create([
            'user_id' => $user->id,
            'collectible_key' => $sticker['key'],
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
            'acquired_at' => now(),
        ]);
    }
}

function cardOfferIndex(array $offers, string $kind): int
{
    foreach ($offers as $index => $offer) {
        if ($offer['kind'] === $kind) {
            return $index;
        }
    }

    throw new RuntimeException("No hay una bolita de tipo {$kind}.");
}

it('genera las mismas bolitas para la misma usuaria y día', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $day = CarbonImmutable::parse('2026-11-03', 'UTC');

    expect($deck->offersFor($this->user, $day))->toBe($deck->offersFor($this->user, $day));
});

it('cambia las bolitas al día siguiente', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);

    $first = $deck->offersFor($this->user, CarbonImmutable::parse('2026-11-03', 'UTC'));
    $second = $deck->offersFor($this->user, CarbonImmutable::parse('2026-11-04', 'UTC'));

    expect($first)->not->toBe($second);
});

it('mezcla cuatro cartas y cuatro stickers cuando hay disponibilidad', function () {
    giveEmpyreanBooks($this->user);

    $offers = app(CardDeckService::class)->offersFor($this->user);

    expect($offers)->toHaveCount(8)
        ->and(collect($offers)->where('kind', 'card'))->toHaveCount(4)
        ->and(collect($offers)->where('kind', 'sticker'))->toHaveCount(4);
});

it('solo ofrece cartas y stickers que la usuaria no tiene', function () {
    giveEmpyreanBooks($this->user);

    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => 'violet',
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);

    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'maquina_libro',
        'kind' => 'sticker',
        'event_key' => 'maquina',
        'source' => 'machine',
        'acquired_at' => now(),
    ]);

    $offers = app(CardDeckService::class)->offersFor($this->user);
    $keys = collect($offers)->pluck('key');

    expect($keys)->not->toContain('violet')
        ->and($keys)->not->toContain('maquina_libro');
});

it('llena la máquina con stickers si no hay libros', function () {
    $offers = app(CardDeckService::class)->offersFor($this->user);

    expect($offers)->toHaveCount(8)
        ->and(collect($offers)->every(fn (array $offer) => $offer['kind'] === 'sticker'))->toBeTrue();
});

it('rellena con cartas si ya no quedan stickers', function () {
    giveEmpyreanBooks($this->user);
    ownAllMachineStickers($this->user);

    $offers = app(CardDeckService::class)->offersFor($this->user);

    expect($offers)->toHaveCount(8)
        ->and(collect($offers)->every(fn (array $offer) => $offer['kind'] === 'card'))->toBeTrue();
});

it('deja la máquina vacía cuando ya tiene todo', function () {
    giveEmpyreanBooks($this->user);
    ownAllCards($this->user);
    ownAllMachineStickers($this->user);

    expect(app(CardDeckService::class)->offersFor($this->user))->toBe([]);
});

it('entrega una carta nueva sin tinta', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $offers = $deck->offersFor($this->user);
    $index = cardOfferIndex($offers, 'card');

    $result = $deck->draw($this->user, $index);

    expect($result['kind'])->toBe('card')
        ->and($result['card']['key'])->toBe($offers[$index]['key'])
        ->and($result['sticker'])->toBeNull()
        ->and($result['ink'])->toBe(0);

    expect($this->user->userCards()->count())->toBe(1)
        ->and($this->user->collectibles()->count())->toBe(0);
});

it('entrega un sticker nuevo sin tinta', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $offers = $deck->offersFor($this->user);
    $index = cardOfferIndex($offers, 'sticker');

    $result = $deck->draw($this->user, $index);

    expect($result['kind'])->toBe('sticker')
        ->and($result['sticker']['key'])->toBe($offers[$index]['key'])
        ->and($result['card'])->toBeNull()
        ->and($result['ink'])->toBe(0);

    expect($this->user->userCards()->count())->toBe(0)
        ->and($this->user->collectibles()->count())->toBe(1)
        ->and($this->user->collectibles()->first()->event_key)->toBe('maquina');
});

it('no vuelve a ofrecer un sticker obtenido', function () {
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $offers = $deck->offersFor($this->user);
    $index = cardOfferIndex($offers, 'sticker');
    $key = $offers[$index]['key'];

    $deck->draw($this->user, $index);

    expect(collect($deck->offersFor($this->user))->pluck('key'))->not->toContain($key);
});

it('rechaza una segunda tirada el mismo día', function () {
    config(['cards.allow_multiple_draws' => false]);
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $deck->draw($this->user, 0);

    expect(fn () => $deck->draw($this->user, 1))
        ->toThrow(ValidationException::class);
});

it('rechaza una bolita fuera de rango', function () {
    giveEmpyreanBooks($this->user);

    expect(fn () => app(CardDeckService::class)->draw($this->user, 99))
        ->toThrow(ValidationException::class);
});

it('rechaza la tirada si no queda nada por obtener', function () {
    giveEmpyreanBooks($this->user);
    ownAllCards($this->user);
    ownAllMachineStickers($this->user);

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

it('permite varias tiradas en modo de pruebas sin repetir', function () {
    config(['cards.allow_multiple_draws' => true]);
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);

    $first = $deck->draw($this->user, 0);
    $second = $deck->draw($this->user, 0);

    $firstKey = $first['card']['key'] ?? $first['sticker']['key'];
    $secondKey = $second['card']['key'] ?? $second['sticker']['key'];

    expect($secondKey)->not->toBe($firstKey)
        ->and($first['ink'])->toBe(0)
        ->and($second['ink'])->toBe(0);
});
```

- [ ] **Step 2: Ejecutar las pruebas y comprobar que fallan**

Run: `php artisan test --filter=CardDrawTest`

Expected: FAIL porque `offersFor()` todavía devuelve claves de carta y `draw()` no soporta stickers.

- [ ] **Step 3: Reescribir `CardDeckService`**

Sustituye el contenido de `app/Services/CardDeckService.php` por:

```php
<?php

namespace App\Services;

use App\Models\CardDraw;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserCollectible;
use App\Models\UserWallet;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Random\Engine\Mt19937;
use Random\Randomizer;

class CardDeckService
{
    public function __construct(
        private CardCatalogService $catalog,
        private EventService $events,
    ) {}

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
            ->whereDate('draw_date', $this->today()->toDateString())
            ->first();
    }

    public function offersFor(User $user, ?CarbonImmutable $day = null): array
    {
        $day ??= $this->today();
        $limit = (int) config('cards.daily_balls');

        $cards = $this->shuffledForDay($this->unownedCardKeys($user), $user, $day);
        $stickers = $this->shuffledForDay($this->unownedStickerKeys($user), $user, $day);

        $cardSlots = min(4, count($cards));
        $stickerSlots = min($limit - $cardSlots, count($stickers));

        if ($cardSlots + $stickerSlots < $limit) {
            $cardSlots = min(count($cards), $limit - $stickerSlots);
        }

        $offers = [
            ...array_map(
                fn (string $key) => ['kind' => 'card', 'key' => $key],
                array_slice($cards, 0, $cardSlots),
            ),
            ...array_map(
                fn (string $key) => ['kind' => 'sticker', 'key' => $key],
                array_slice($stickers, 0, $stickerSlots),
            ),
        ];

        $randomizer = new Randomizer(new Mt19937(
            crc32($user->id.'|'.config('app.key').'|mix|'.$day->toDateString())
        ));
        $randomizer->shuffleArray($offers);

        return $offers;
    }

    public function draw(User $user, int $ballIndex): array
    {
        $day = $this->today();
        $offers = $this->offersFor($user, $day);

        if ($offers === []) {
            throw ValidationException::withMessages(['ball' => __('ui.cards_complete')]);
        }

        if ($ballIndex < 0 || $ballIndex >= count($offers)) {
            throw ValidationException::withMessages(['ball' => __('ui.cards_invalid_ball')]);
        }

        $item = $offers[$ballIndex];

        try {
            $result = DB::transaction(function () use ($user, $day, $ballIndex, $item) {
                $existing = CardDraw::query()
                    ->where('user_id', $user->id)
                    ->whereDate('draw_date', $day->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null && ! config('cards.allow_multiple_draws')) {
                    throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
                }

                if ($item['kind'] === 'card') {
                    $owned = UserCard::firstOrCreate(
                        ['user_id' => $user->id, 'card_key' => $item['key']],
                        ['copies' => 1, 'first_unlocked_at' => now()],
                    );

                    if (! $owned->wasRecentlyCreated) {
                        throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
                    }

                    $payload = [
                        'kind' => 'card',
                        'card' => $this->catalog->metadata($item['key']),
                        'sticker' => null,
                    ];
                } else {
                    $owned = UserCollectible::firstOrCreate(
                        ['user_id' => $user->id, 'collectible_key' => $item['key']],
                        [
                            'kind' => 'sticker',
                            'event_key' => 'maquina',
                            'source' => 'machine',
                            'acquired_at' => now(),
                        ],
                    );

                    if (! $owned->wasRecentlyCreated) {
                        throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
                    }

                    $payload = [
                        'kind' => 'sticker',
                        'card' => null,
                        'sticker' => $this->catalog->machineStickerMetadata($item['key']),
                    ];
                }

                $drawAttributes = [
                    'kind' => $item['kind'],
                    'item_key' => $item['key'],
                    'ball_index' => $ballIndex,
                    'duplicate' => false,
                    'ink_earned' => 0,
                ];

                if ($existing !== null) {
                    $existing->update($drawAttributes);
                } else {
                    CardDraw::create($drawAttributes + [
                        'user_id' => $user->id,
                        'draw_date' => $day->toDateString(),
                    ]);
                }

                $wallet = UserWallet::firstOrCreate(['user_id' => $user->id]);

                return $payload + [
                    'ink' => (int) $wallet->fresh()->ink,
                    'resetAt' => $this->nextResetAt()->toIso8601String(),
                ];
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
            }

            throw $exception;
        }

        $this->events->completeTask($user, 'draw');

        return $result;
    }

    private function unownedCardKeys(User $user): array
    {
        $owned = $user->userCards()->pluck('card_key')->all();

        return array_values(array_diff($this->catalog->poolFor($user), $owned));
    }

    private function unownedStickerKeys(User $user): array
    {
        $owned = $user->collectibles()
            ->where('kind', 'sticker')
            ->where('event_key', 'maquina')
            ->pluck('collectible_key')
            ->all();

        return array_values(array_filter(
            array_column($this->catalog->machineStickers(), 'key'),
            fn (string $key) => ! in_array($key, $owned, true),
        ));
    }

    private function shuffledForDay(array $keys, User $user, CarbonImmutable $day): array
    {
        if ($keys === []) {
            return [];
        }

        $randomizer = new Randomizer(new Mt19937(
            crc32($user->id.'|'.config('app.key').'|'.$day->toDateString())
        ));
        $randomizer->shuffleArray($keys);

        return $keys;
    }
}
```

- [ ] **Step 4: Ejecutar las pruebas**

Run: `php artisan test --filter=CardDrawTest`

Expected: PASS.

- [ ] **Step 5: Ejecutar las pruebas de cartas completas**

Run: `php artisan test --filter='CardDrawTest|CardDrawModelTest|CardCatalogTest'`

Expected: PASS.

- [ ] **Step 6: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 4: Props de página, resultado persistido y álbum con stickers

**Files:**
- Modify: `app/Http/Controllers/CardController.php`
- Modify: `tests/Feature/CardPageTest.php`

- [ ] **Step 1: Reescribir `tests/Feature/CardPageTest.php`**

Sustituye el contenido completo por:

```php
<?php

use App\Models\Book;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserCollectible;
use App\Services\CardDeckService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['events.enabled' => false]);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de sangre',
        'author' => 'Rebecca Yarros',
    ]);
});

it('carga la página de cartas con cartas, stickers y bolitas', function () {
    $this->get(route('cards.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cards/Index')
            ->has('cards', 9)
            ->has('offers', 8)
            ->has('stickers', 20)
            ->where('progress.total', 9)
            ->where('progress.unlocked', 0)
            ->where('stickerProgress.total', 20)
            ->where('stickerProgress.unlocked', 0)
            ->where('complete', false)
            ->where('drawn', null)
            ->where('ink', 0)
        );
});

it('no expone el tipo ni la clave en las bolitas', function () {
    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('offers.0.key')
            ->missing('offers.0.kind')
            ->missing('offers.0.family')
            ->missing('offers.0.character')
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
    config(['cards.allow_multiple_draws' => false]);

    $this->postJson(route('cards.draw'), ['ball' => 0])
        ->assertOk()
        ->assertJsonStructure(['kind', 'card', 'sticker', 'ink', 'resetAt']);

    $this->postJson(route('cards.draw'), ['ball' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('ball');
});

it('guarda una carta nueva sin tinta', function () {
    $offers = app(CardDeckService::class)->offersFor($this->user);
    $index = collect($offers)->search(fn (array $offer) => $offer['kind'] === 'card');

    $this->postJson(route('cards.draw'), ['ball' => $index])
        ->assertOk()
        ->assertJsonPath('kind', 'card')
        ->assertJsonPath('sticker', null)
        ->assertJsonPath('ink', 0);
});

it('guarda un sticker nuevo y lo muestra al recargar', function () {
    $offers = app(CardDeckService::class)->offersFor($this->user);
    $index = collect($offers)->search(fn (array $offer) => $offer['kind'] === 'sticker');
    $key = $offers[$index]['key'];

    $this->postJson(route('cards.draw'), ['ball' => $index])
        ->assertOk()
        ->assertJsonPath('kind', 'sticker')
        ->assertJsonPath('sticker.key', $key)
        ->assertJsonPath('card', null);

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('drawn.kind', 'sticker')
            ->where('drawn.sticker.key', $key)
            ->where('drawn.card', null)
            ->where('stickerProgress.unlocked', 1)
        );
});

it('deja la máquina activa tras tirar en modo de pruebas', function () {
    config(['cards.allow_multiple_draws' => true]);

    $this->postJson(route('cards.draw'), ['ball' => 0])->assertOk();
    $this->postJson(route('cards.draw'), ['ball' => 1])->assertOk();

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('drawn', null)
        );
});

it('funciona sin libros ofreciendo solo stickers', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cards', 0)
            ->has('offers', 8)
            ->where('progress.total', 0)
            ->where('complete', false)
        );
});

it('marca la colección completa cuando ya no queda nada', function () {
    foreach (array_keys(config('cards.cards')) as $key) {
        UserCard::create([
            'user_id' => $this->user->id,
            'card_key' => $key,
            'copies' => 1,
            'first_unlocked_at' => now(),
        ]);
    }

    foreach (config('cards.machine_stickers') as $sticker) {
        UserCollectible::create([
            'user_id' => $this->user->id,
            'collectible_key' => $sticker['key'],
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
            'acquired_at' => now(),
        ]);
    }

    Book::factory()->for($this->user)->create([
        'title' => 'El mapa de los anhelos',
        'author' => 'Alice Kellen',
    ]);

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('offers', 0)
            ->where('complete', true)
        );
});

it('carga el álbum de cartas y stickers como página propia', function () {
    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => 'violet',
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);

    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'maquina_libro',
        'kind' => 'sticker',
        'event_key' => 'maquina',
        'source' => 'machine',
        'acquired_at' => now(),
    ]);

    $this->get(route('cards.album'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cards/Album')
            ->has('cards', 9)
            ->has('stickers', 20)
            ->where('progress.unlocked', 1)
            ->where('progress.total', 9)
            ->where('stickerProgress.unlocked', 1)
            ->where('stickerProgress.total', 20)
            ->where('ink', 0)
        );
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

- [ ] **Step 2: Ejecutar las pruebas y comprobar que fallan**

Run: `php artisan test --filter=CardPageTest`

Expected: FAIL porque el controlador todavía no envía `stickers`, `stickerProgress` ni `complete`.

- [ ] **Step 3: Reescribir `CardController`**

Sustituye el contenido completo de `app/Http/Controllers/CardController.php` por:

```php
<?php

namespace App\Http\Controllers;

use App\Models\CardDraw;
use App\Models\User;
use App\Services\CardCatalogService;
use App\Services\CardDeckService;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $drawn = config('cards.allow_multiple_draws') ? null : $deck->drawnToday($user);
        $offers = collect($deck->offersFor($user))
            ->values()
            ->map(fn (array $offer, int $index) => ['index' => $index]);

        return Inertia::render('Cards/Index', [
            'cards' => $this->cardsData($pool, $catalog, $owned),
            'offers' => $offers,
            'drawn' => $drawn ? $this->drawnData($drawn, $catalog, $owned) : null,
            'ink' => (int) ($user->wallet?->ink ?? 0),
            'progress' => [
                'unlocked' => $owned->count(),
                'total' => count($pool),
            ],
            'resetAt' => $deck->nextResetAt()->toIso8601String(),
            'dailyBalls' => (int) config('cards.daily_balls'),
            'complete' => $offers->isEmpty(),
        ] + $this->stickerData($user, $catalog));
    }

    public function album(Request $request, CardCatalogService $catalog, EventService $events): Response
    {
        $user = $request->user();
        $pool = $catalog->poolFor($user);
        $owned = $user->userCards()->whereIn('card_key', $pool)->get()->keyBy('card_key');

        $events->completeTask($user, 'album');

        return Inertia::render('Cards/Album', [
            'cards' => $this->cardsData($pool, $catalog, $owned),
            'progress' => [
                'unlocked' => $owned->count(),
                'total' => count($pool),
            ],
            'ink' => (int) ($user->wallet?->ink ?? 0),
        ] + $this->stickerData($user, $catalog));
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

    private function cardsData(array $pool, CardCatalogService $catalog, Collection $owned): Collection
    {
        return collect($pool)->map(function (string $key) use ($catalog, $owned) {
            $card = $owned->get($key);

            return $catalog->metadata($key) + [
                'unlocked' => $card !== null,
                'copies' => (int) ($card->copies ?? 0),
            ];
        })->values();
    }

    private function stickerData(User $user, CardCatalogService $catalog): array
    {
        $catalogStickers = $catalog->machineStickers();
        $owned = $user->collectibles()
            ->where('kind', 'sticker')
            ->where('event_key', 'maquina')
            ->get()
            ->keyBy('collectible_key');

        $stickers = collect($catalogStickers)->map(function (array $sticker) use ($owned) {
            $record = $owned->get($sticker['key']);

            return [
                'key' => $sticker['key'],
                'name' => __($sticker['name']),
                'unlocked' => $record !== null,
                'acquired_at' => $record?->acquired_at?->toISOString(),
            ];
        })->values();

        return [
            'stickers' => $stickers,
            'stickerProgress' => [
                'unlocked' => $owned->count(),
                'total' => count($catalogStickers),
            ],
        ];
    }

    private function drawnData(CardDraw $draw, CardCatalogService $catalog, Collection $ownedCards): array
    {
        if ($draw->kind === 'sticker') {
            return [
                'kind' => 'sticker',
                'card' => null,
                'sticker' => $catalog->machineStickerMetadata($draw->item_key),
            ];
        }

        return [
            'kind' => 'card',
            'card' => $catalog->metadata($draw->item_key),
            'sticker' => null,
            'copies' => (int) ($ownedCards->get($draw->item_key)?->copies ?? 0),
        ];
    }
}
```

- [ ] **Step 4: Ejecutar las pruebas**

Run: `php artisan test --filter=CardPageTest`

Expected: PASS.

- [ ] **Step 5: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 5: Aislar los stickers de la máquina de los eventos

**Files:**
- Modify: `app/Http/Controllers/EventController.php`
- Modify: `app/Services/EventService.php`
- Modify: `tests/Feature/EventAlbumTest.php`
- Modify: `tests/Feature/EventPageTest.php`

- [ ] **Step 1: Añadir las pruebas que fallan**

En `tests/Feature/EventPageTest.php`, añade:

```php
it('no muestra los stickers de la máquina en la colección de eventos', function () {
    \App\Models\UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'maquina_libro',
        'kind' => 'sticker',
        'event_key' => 'maquina',
        'source' => 'machine',
        'acquired_at' => now(),
    ]);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('collection', 0));
});
```

En `tests/Feature/EventAlbumTest.php`, añade:

```php
it('no cuenta los stickers de la máquina en el álbum de eventos', function () {
    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'maquina_libro',
        'kind' => 'sticker',
        'event_key' => 'maquina',
        'source' => 'machine',
        'acquired_at' => now(),
    ]);

    $this->get(route('events.album'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('stickers', 12)
            ->where('progress.owned', 0)
            ->where('progress.total', 12)
        );
});
```

- [ ] **Step 2: Ejecutar las pruebas y comprobar que fallan**

Run: `php artisan test --filter='EventPageTest|EventAlbumTest'`

Expected: FAIL porque las consultas de eventos incluyen el colectable `maquina_libro`.

- [ ] **Step 3: Filtrar la colección de eventos**

En `app/Http/Controllers/EventController.php`, dentro de `index()`, cambia el mapeo de `$collection` por:

```php
$collection = collect($events->collection($user))
    ->map(function ($row) use ($events) {
        $meta = $events->catalogItem($row->collectible_key);

        if ($meta === null) {
            return null;
        }

        return [
            'key' => $row->collectible_key,
            'kind' => $row->kind,
            'name' => __("ui.event_item_{$row->collectible_key}"),
            'icon' => $meta['emblem'] ?? $meta['initial'] ?? null,
            'acquired_at' => $row->acquired_at?->toISOString(),
        ];
    })
    ->filter()
    ->values();
```

Conserva el resto del arreglo de retorno tal como está.

- [ ] **Step 4: Filtrar el álbum de eventos**

En `app/Http/Controllers/EventController.php`, dentro de `album()`, cambia la consulta de `$owned` por:

```php
$eventKeys = collect(config('events.catalog'))->pluck('key')->all();

$owned = $user->collectibles()
    ->where('kind', 'sticker')
    ->whereIn('event_key', $eventKeys)
    ->get()
    ->keyBy('collectible_key');
```

- [ ] **Step 5: Filtrar las claves de colectables de evento**

En `app/Services/EventService.php`, cambia `ownedCollectibleKeys()` por:

```php
public function ownedCollectibleKeys(User $user): array
{
    $eventKeys = collect(config('events.catalog'))->pluck('key')->all();

    return $user->collectibles()
        ->whereIn('event_key', $eventKeys)
        ->pluck('collectible_key')
        ->all();
}
```

- [ ] **Step 6: Ejecutar las pruebas de eventos**

Run: `php artisan test --filter='EventPageTest|EventAlbumTest|EventShopTest|EventProgressTest'`

Expected: PASS.

- [ ] **Step 7: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 6: Componente `LibrarySticker` con 20 artes troquelados

**Files:**
- Create: `resources/js/Components/LibrarySticker.vue`
- Create: `resources/js/Components/__tests__/LibrarySticker.test.js`
- Modify: `resources/css/textures.css`

- [ ] **Step 1: Escribir la prueba que falla**

Crea `resources/js/Components/__tests__/LibrarySticker.test.js`:

```js
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import LibrarySticker from '../LibrarySticker.vue';

enableAutoUnmount(afterEach);

const keys = [
    'maquina_libro',
    'maquina_pluma',
    'maquina_tintero',
    'maquina_taza',
    'maquina_gato',
    'maquina_luna',
    'maquina_vela',
    'maquina_planta',
    'maquina_lupa',
    'maquina_llave',
    'maquina_gafas',
    'maquina_marcapaginas',
    'maquina_reloj',
    'maquina_buho',
    'maquina_pocion',
    'maquina_pergamino',
    'maquina_maquina_escribir',
    'maquina_rosa',
    'maquina_cuervo',
    'maquina_cristal',
];

describe('LibrarySticker', () => {
    it('renderiza los 20 artes de la colección', () => {
        for (const key of keys) {
            const wrapper = mount(LibrarySticker, { props: { sticker: key } });

            expect(wrapper.find('.library-sticker__art').exists()).toBe(true);
            expect(wrapper.find('.library-sticker__cut').exists()).toBe(true);
        }
    });

    it('muestra el interrogante cuando está bloqueado', () => {
        const wrapper = mount(LibrarySticker, {
            props: { sticker: 'maquina_libro', locked: true },
        });

        expect(wrapper.find('.library-sticker__locked').exists()).toBe(true);
        expect(wrapper.find('.library-sticker__art').exists()).toBe(false);
    });
});
```

- [ ] **Step 2: Ejecutar la prueba y comprobar que falla**

Run: `npx vitest run resources/js/Components/__tests__/LibrarySticker.test.js`

Expected: FAIL porque el componente no existe.

- [ ] **Step 3: Crear `LibrarySticker.vue`**

Crea `resources/js/Components/LibrarySticker.vue`:

```vue
<script setup>
import { computed } from 'vue';

const props = defineProps({
    sticker: { type: String, default: 'maquina_libro' },
    size: { type: Number, default: 96 },
    locked: { type: Boolean, default: false },
});

const cuts = [
    'M60 4c15-1 31 5 40 17 9 12 12 29 5 43-7 14-22 24-39 25-17 1-34-6-43-19C14 57 11 39 19 26 27 13 44 5 60 4Z',
    'M58 5c17-2 34 5 43 19 9 14 8 32-1 45-9 13-25 21-42 20-17-1-32-10-40-24C10 51 13 33 23 21 33 10 44 7 58 5Z',
    'M60 5c16 0 33 7 41 21 8 14 7 32-2 44-9 12-25 19-41 19-16 0-32-7-41-19-9-12-10-30-2-44C23 12 44 5 60 5Z',
];

const artworks = {
    maquina_libro: `
        <path d="M22 36c14-6 26-6 38 2 12-8 24-8 38-2v48c-14-6-26-6-38 2-12-8-24-8-38-2V36Z" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2.4"/>
        <path d="M60 38v46" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M30 42c10-3 18-2 24 2M30 52c10-3 18-2 24 2M30 62c10-3 18-2 24 2M66 44c6-4 14-5 24-2M66 54c6-4 14-5 24-2M66 64c6-4 14-5 24-2" fill="none" stroke="var(--color-dorado)" stroke-width="1.8"/>
        <path d="M88 26l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_pluma: `
        <path d="M34 92c14-22 30-44 52-58 4-3 8 0 7 5-7 24-24 43-45 55" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M86 36c-12 16-26 30-43 41" fill="none" stroke="var(--color-dorado)" stroke-width="2"/>
        <path d="M48 74l-8 18M58 64l-9 9M66 54l-9 8M74 46l-8 7" stroke="var(--color-tinta)" stroke-width="1.6" fill="none"/>
        <circle cx="30" cy="38" r="2.2" fill="var(--color-accent)"/>
    `,
    maquina_tintero: `
        <path d="M40 52h40l-5 30a10 10 0 0 1-10 9H55a10 10 0 0 1-10-9l-5-30Z" fill="var(--color-tertiary)" opacity=".7" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <rect x="42" y="44" width="36" height="9" rx="3" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M50 40c0-8 4-12 10-12s10 4 10 12" fill="none" stroke="var(--color-tinta)" stroke-width="2"/>
        <circle cx="60" cy="66" r="6" fill="var(--color-crema)" opacity=".7"/>
        <path d="M84 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_taza: `
        <path d="M34 48h42v18a16 16 0 0 1-16 16H50a16 16 0 0 1-16-16V48Z" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M76 54h7a8 8 0 0 1 0 16h-7" fill="none" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M34 48h42" stroke="var(--color-dorado)" stroke-width="3"/>
        <path d="M46 40c2-4 2-7 0-10M56 40c2-4 2-7 0-10M66 40c2-4 2-7 0-10" fill="none" stroke="var(--color-ink-muted)" stroke-width="1.8" opacity=".6"/>
        <path d="M28 88h58" stroke="var(--color-madera)" stroke-width="3"/>
    `,
    maquina_gato: `
        <path d="M38 54l-4-18 16 8c3-1 7-1 10 0l16-8-4 18c4 5 6 11 6 17 0 14-10 22-23 22S32 85 32 71c0-6 2-12 6-17Z" fill="var(--color-madera-clara)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M48 66c2 2 5 2 7 0M65 66c2 2 5 2 7 0" fill="none" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M58 74l-3 5h6l-3-5Z" fill="var(--color-accent)"/>
        <path d="M44 76l-10-3M46 80l-11 2M76 76l10-3M74 80l11 2" stroke="var(--color-tinta)" stroke-width="1.4"/>
        <path d="M44 54c4-2 8-2 12 0M64 54c4-2 8-2 12 0" fill="none" stroke="var(--color-dorado)" stroke-width="1.6"/>
    `,
    maquina_luna: `
        <path d="M70 18c-18 4-30 20-27 38 3 19 20 32 39 29-10 10-25 14-39 10C23 90 11 70 16 50 21 31 41 18 61 18c3 0 6 0 9 1Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <circle cx="42" cy="44" r="2" fill="var(--color-accent)"/>
        <path d="M86 26l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_vela: `
        <path d="M48 46h24v38H48z" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M60 46c-6-5-6-13 0-22 6 9 6 17 0 22Z" fill="var(--color-accent)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M44 84h32v10H44z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M52 54c2 2 4 4 4 6a4 4 0 0 1-8 0c0-2 2-4 4-6Z" fill="var(--color-terracota)"/>
        <path d="M84 34l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_planta: `
        <path d="M36 78h48l-7 28H43l-7-28Z" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <rect x="32" y="72" width="56" height="9" rx="4" fill="var(--color-madera-clara)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M60 73V38" stroke="var(--color-bosque)" stroke-width="3"/>
        <path d="M60 58c-11-2-17-9-18-19 10 0 18 6 20 16" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.8"/>
        <path d="M60 50c11-2 17-9 18-19-10 0-18 6-20 16" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.8"/>
        <path d="M60 38c-6-2-10-7-11-13 7 0 12 4 14 10" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M60 44c6-2 10-7 11-13-7 0-12 4-14 10" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.6"/>
    `,
    maquina_lupa: `
        <circle cx="52" cy="52" r="24" fill="var(--color-secondary)" opacity=".25" stroke="var(--color-dorado)" stroke-width="4"/>
        <path d="M70 70l20 20" stroke="var(--color-madera)" stroke-width="8" stroke-linecap="round"/>
        <path d="M70 70l20 20" stroke="var(--color-dorado)" stroke-width="3"/>
        <path d="M38 52c2-8 8-14 16-16" fill="none" stroke="var(--color-crema)" stroke-width="2" opacity=".7"/>
        <path d="M88 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_llave: `
        <circle cx="42" cy="60" r="16" fill="none" stroke="var(--color-dorado)" stroke-width="6"/>
        <circle cx="42" cy="60" r="7" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M56 60h32M78 60v10M88 60v8" stroke="var(--color-dorado)" stroke-width="6" stroke-linecap="round"/>
        <path d="M56 60h32M78 60v10M88 60v8" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M84 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_gafas: `
        <circle cx="40" cy="58" r="15" fill="var(--color-secondary)" opacity=".22" stroke="var(--color-tinta)" stroke-width="3"/>
        <circle cx="78" cy="58" r="15" fill="var(--color-secondary)" opacity=".22" stroke="var(--color-tinta)" stroke-width="3"/>
        <path d="M55 56c4-3 8-3 12 0" fill="none" stroke="var(--color-tinta)" stroke-width="3"/>
        <path d="M25 54l-8-10M93 54l8-10" stroke="var(--color-tinta)" stroke-width="3" stroke-linecap="round"/>
        <path d="M34 52c3-3 8-3 11 0M72 52c3-3 8-3 11 0" fill="none" stroke="var(--color-crema)" stroke-width="2" opacity=".7"/>
        <path d="M88 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_marcapaginas: `
        <path d="M42 22h36v70l-18-12-18 12V22Z" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M42 22h36v8H42z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M54 44c4-4 8-4 12 0M54 56c4-4 8-4 12 0" fill="none" stroke="var(--color-crema)" stroke-width="2" opacity=".8"/>
        <path d="M36 34l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_reloj: `
        <circle cx="60" cy="64" r="28" fill="var(--color-crema)" stroke="var(--color-dorado)" stroke-width="4"/>
        <circle cx="60" cy="64" r="22" fill="none" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M60 64V48M60 64l10 7" stroke="var(--color-tinta)" stroke-width="2.4" stroke-linecap="round"/>
        <circle cx="60" cy="26" r="6" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M84 34l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_buho: `
        <path d="M36 42c0-14 10-22 24-22s24 8 24 22v20c0 16-10 26-24 26S36 78 36 62V42Z" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <circle cx="48" cy="50" r="8" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2"/>
        <circle cx="72" cy="50" r="8" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2"/>
        <circle cx="48" cy="50" r="3" fill="var(--color-tinta)"/>
        <circle cx="72" cy="50" r="3" fill="var(--color-tinta)"/>
        <path d="M60 58l-6 8h12l-6-8Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M45 70c4 3 9 4 15 4s11-1 15-4" fill="none" stroke="var(--color-dorado)" stroke-width="2"/>
    `,
    maquina_pocion: `
        <path d="M48 30h24v12l10 16a20 20 0 0 1-17 30H55A20 20 0 0 1 38 58l10-16V30Z" fill="var(--color-secondary)" opacity=".45" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M42 62a18 18 0 0 0 36 0c0-7-8-9-18-9s-18 2-18 9Z" fill="var(--color-tertiary)" opacity=".75" stroke="var(--color-tinta)" stroke-width="2"/>
        <rect x="46" y="20" width="28" height="10" rx="3" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2"/>
        <circle cx="52" cy="64" r="3" fill="var(--color-secondary)"/>
        <circle cx="66" cy="70" r="2.4" fill="var(--color-secondary)"/>
        <path d="M86 34l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_pergamino: `
        <path d="M36 28h48v56c0 6-5 10-10 10H36V28Z" fill="var(--color-pergamino)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M36 28c-6 0-10 4-10 10v46h10" fill="none" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M44 44h32M44 54h32M44 64h24" stroke="var(--color-dorado)" stroke-width="2"/>
        <path d="M82 84c6 0 10-4 10-10" fill="none" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M88 32l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_maquina_escribir: `
        <rect x="28" y="56" width="64" height="30" rx="6" fill="var(--color-tinta)" stroke="var(--color-tinta)" stroke-width="2"/>
        <rect x="36" y="62" width="48" height="8" rx="2" fill="var(--color-crema)" opacity=".8"/>
        <path d="M38 86h44v10H38z" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M28 56h64" stroke="var(--color-dorado)" stroke-width="3"/>
        <path d="M60 56V30" stroke="var(--color-tinta)" stroke-width="2.4"/>
        <path d="M60 30c-8 0-14 4-16 10M60 30c8 0 14 4 16 10" fill="none" stroke="var(--color-dorado)" stroke-width="2"/>
        <path d="M84 36l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_rosa: `
        <path d="M42 92c8-4 16-4 24 0 6-3 12-3 18 0" fill="none" stroke="var(--color-bosque)" stroke-width="3"/>
        <path d="M60 86V56" stroke="var(--color-bosque)" stroke-width="4"/>
        <path d="M58 74c-10-2-16-8-18-16 9 0 16 5 20 13" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M62 68c10-2 16-8 18-16-9 0-16 5-20 13" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M60 30c11-8 23-1 21 10 9 3 10 14 2 19 3 10-8 16-16 11-6 9-19 5-20-5-12 0-16-12-9-19-5-9 5-19 15-17 2 1 4 1 7 1Z" fill="var(--color-danger)" stroke="var(--color-tinta)" stroke-width="2"/>
        <circle cx="60" cy="46" r="7" fill="var(--color-danger-strong)" stroke="var(--color-tinta)" stroke-width="1.4"/>
        <path d="M60 42c2 2 4 4 4 6a4 4 0 0 1-8 0c0-2 2-4 4-6Z" fill="var(--color-danger)"/>
    `,
    maquina_cuervo: `
        <path d="M36 62c0-18 11-30 26-30 12 0 21 7 24 18 8 2 12 8 12 16-8-3-14-2-19 3-5 9-13 14-23 14-12 0-20-8-20-21Z" fill="var(--color-tinta)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M66 48c3-2 6-2 9 0" fill="none" stroke="var(--color-crema)" stroke-width="2"/>
        <circle cx="70" cy="44" r="2.2" fill="var(--color-crema)"/>
        <path d="M88 54l10 4-10 4v-8Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M50 78l-8 10M58 80l-4 12M68 80l4 12M76 78l8 10" stroke="var(--color-tinta)" stroke-width="2" fill="none"/>
        <path d="M34 44l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    maquina_cristal: `
        <path d="M60 18l22 18-8 48-14 16-14-16-8-48 22-18Z" fill="var(--color-tertiary)" opacity=".55" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M60 18v82M38 36h44M42 54h36M46 68h28" stroke="var(--color-crema)" stroke-width="1.8" opacity=".6"/>
        <path d="M60 26l10 10-10 10-10-10 10-10Z" fill="var(--color-crema)" opacity=".7"/>
        <path d="M86 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
};

const keys = Object.keys(artworks);

const cut = computed(() => {
    const index = keys.indexOf(props.sticker);

    return cuts[(index < 0 ? 0 : index) % cuts.length];
});

const art = computed(() => artworks[props.sticker] ?? artworks.maquina_libro);
</script>

<template>
    <span
        class="library-sticker"
        :class="{ 'library-sticker--locked': locked }"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
        <svg class="library-sticker__svg" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
            <path class="library-sticker__cut" :d="cut" />

            <g v-if="!locked" class="library-sticker__art" v-html="art" />
            <g v-else class="library-sticker__locked">
                <path
                    :d="cut"
                    fill="var(--color-surface-raised)"
                    opacity=".8"
                    transform="translate(60 60) scale(.82) translate(-60 -60)"
                />
                <text
                    x="60"
                    y="72"
                    text-anchor="middle"
                    font-size="30"
                    font-family="Georgia, serif"
                    fill="var(--color-ink-muted)"
                >?</text>
            </g>
        </svg>
    </span>
</template>
```

- [ ] **Step 4: Añadir los estilos troquelados**

En `resources/css/textures.css`, después del bloque `.event-sticker--locked .event-sticker__svg`, añade:

```css
.library-sticker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
}

.library-sticker__svg {
  width: 100%;
  height: 100%;
  overflow: visible;
  filter: drop-shadow(0 4px 4px rgb(0 0 0 / 30%));
}

.library-sticker__cut {
  fill: var(--color-crema);
}

.library-sticker__art *,
.library-sticker__locked * {
  stroke-linecap: round;
  stroke-linejoin: round;
}

.library-sticker--locked .library-sticker__svg {
  filter: drop-shadow(0 2px 2px rgb(0 0 0 / 18%));
}
```

- [ ] **Step 5: Ejecutar la prueba**

Run: `npx vitest run resources/js/Components/__tests__/LibrarySticker.test.js`

Expected: PASS.

- [ ] **Step 6: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 7: Resultado de sticker y colección completa en `CardMachine`

**Files:**
- Modify: `resources/js/Components/CardMachine.vue`
- Modify: `resources/js/Components/__tests__/CardMachine.test.js`

- [ ] **Step 1: Actualizar la prueba de `CardMachine`**

Sustituye `resources/js/Components/__tests__/CardMachine.test.js` por:

```js
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { h, reactive } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import CardMachine from '../CardMachine.vue';

enableAutoUnmount(afterEach);

const offers = [{ index: 0 }, { index: 1 }, { index: 2 }];

const cardResult = {
    kind: 'card',
    card: {
        key: 'violet',
        family: 'empireo',
        familyLabel: 'Empíreo',
        familyEmblem: 'dragon',
        character: 'Violet Sorrengail',
        monogram: 'VS',
        emblem: 'dragon',
    },
    sticker: null,
};

const stickerResult = {
    kind: 'sticker',
    card: null,
    sticker: {
        key: 'maquina_libro',
        name: 'Libro abierto',
    },
};

function mountMachine(props = {}) {
    return mount(CardMachine, {
        props: {
            offers,
            drawing: false,
            result: null,
            resetAt: '2026-11-04T05:00:00Z',
            complete: false,
            ...props,
        },
    });
}

describe('CardMachine', () => {
    beforeEach(() => {
        mocks.page = reactive({ props: { locale: 'es', translations: {} } });
    });

    it('monta la escena, la ventana y la trampilla', () => {
        const wrapper = mountMachine();

        expect(wrapper.find('.card-machine__scene').exists()).toBe(true);
        expect(wrapper.find('.card-machine__window').exists()).toBe(true);
        expect(wrapper.find('.card-machine__dome').exists()).toBe(true);
        expect(wrapper.find('.card-machine__lever').exists()).toBe(true);
        expect(wrapper.find('.card-machine__slot').exists()).toBe(true);
    });

    it('deja la manivela lista para girar sin elegir perla', () => {
        const wrapper = mountMachine();

        const lever = wrapper.get('.card-machine__lever');

        expect(lever.attributes('disabled')).toBeUndefined();
        expect(wrapper.text()).toContain('Gira la manivela para sacar una sorpresa.');
    });

    it('emite crank con un índice de perla válido al pulsar', async () => {
        const wrapper = mountMachine();

        await wrapper.get('.card-machine__lever').trigger('click');

        const emitted = wrapper.emitted('crank');

        expect(emitted).toHaveLength(1);
        expect(emitted[0][0]).toBeGreaterThanOrEqual(0);
        expect(emitted[0][0]).toBeLessThan(offers.length);
    });

    it('bloquea la manivela mientras dibuja', () => {
        const wrapper = mountMachine({ drawing: true });

        const lever = wrapper.get('.card-machine__lever');

        expect(lever.attributes('disabled')).toBeDefined();
        expect(lever.classes()).toContain('card-machine__crank--turning');
        expect(wrapper.text()).toContain('Girando');
    });

    it('muestra el sobre cuando el resultado es una carta', async () => {
        const wrapper = mountMachine({ result: cardResult });

        expect(wrapper.get('.card-envelope').exists()).toBe(true);
        expect(wrapper.text()).toContain('Carta nueva');

        await wrapper.get('.card-envelope').trigger('click');

        expect(wrapper.emitted('open')).toEqual([[]]);
    });

    it('muestra el sticker y no emite open', async () => {
        const wrapper = mountMachine({ result: stickerResult });

        expect(wrapper.find('.library-sticker').exists()).toBe(true);
        expect(wrapper.text()).toContain('Libro abierto');
        expect(wrapper.text()).toContain('¡Sticker nuevo!');

        await wrapper.get('.library-sticker').trigger('click');

        expect(wrapper.emitted('open')).toBeUndefined();
    });

    it('muestra la colección completa sin manivela', () => {
        const wrapper = mountMachine({ complete: true });

        expect(wrapper.text()).toContain('¡Colección completa!');
        expect(wrapper.find('.card-machine__lever').exists()).toBe(false);
    });
});
```

- [ ] **Step 2: Ejecutar la prueba y comprobar que falla**

Run: `npx vitest run resources/js/Components/__tests__/CardMachine.test.js`

Expected: FAIL porque `CardMachine` todavía espera `result.duplicate` y no conoce `complete` ni los stickers.

- [ ] **Step 3: Importar `LibrarySticker` y añadir la prop `complete`**

En el `<script setup>` de `resources/js/Components/CardMachine.vue`:

```js
import LibrarySticker from './LibrarySticker.vue';
```

Y en `defineProps`:

```js
complete: { type: Boolean, default: false },
```

- [ ] **Step 4: Cambiar el bloque de resultado**

Sustituye el bloque `<template v-if="result">...</template>` por:

```vue
<template v-if="result">
    <template v-if="result.kind === 'sticker'">
        <p class="mb-3 text-center font-serif text-lg text-accent">
            {{ t('ui.cards_sticker_new') }}
        </p>
        <div class="flex flex-col items-center gap-3">
            <LibrarySticker :sticker="result.sticker.key" :size="170" />
            <p class="text-center font-serif text-lg text-ink">{{ result.sticker.name }}</p>
        </div>
    </template>
    <template v-else>
        <p class="mb-3 text-center font-serif text-lg text-accent">
            {{ t('ui.cards_new_card') }}
        </p>
        <CardEnvelope :card="result.card" @open="emit('open')" />
    </template>
    <p v-if="countdown" class="mt-4 text-center text-sm text-ink-muted">
        {{ t('ui.cards_reset_in', { time: countdownText }) }}
    </p>
</template>
```

- [ ] **Step 5: Añadir el estado de colección completa**

Envuelve el cuerpo y la región `aria-live` en un `v-else` y añade el mensaje de completo. El `<template>` completo de `CardMachine` debe quedar así:

```vue
<template>
    <section class="card-machine mx-auto w-full max-w-md">
        <div v-if="complete" class="rounded-card border border-edge bg-surface p-6 text-center">
            <p class="font-serif text-xl text-accent">{{ t('ui.cards_complete') }}</p>
            <p class="mt-2 text-ink-muted">{{ t('ui.cards_complete_help') }}</p>
        </div>

        <template v-else>
            <div class="card-machine__body" :class="{ 'card-machine__body--cranking': drawing }">
                <div ref="sceneEl" class="card-machine__scene" aria-hidden="true"></div>

                <div class="card-machine__window">
                    <div class="card-machine__dome"></div>
                </div>

                <div class="card-machine__panel">
                    <button
                        type="button"
                        class="card-machine__crank card-machine__lever"
                        :class="{ 'card-machine__crank--turning': drawing }"
                        :disabled="drawing"
                        :aria-label="t('ui.cards_crank')"
                        @click="pull"
                    ></button>
                </div>

                <div class="card-machine__slot" :class="{ 'card-machine__slot--open': !!result }" aria-hidden="true"></div>
            </div>

            <div aria-live="polite" class="mt-6">
                <p v-if="!result" class="text-center text-ink-muted">{{ t('ui.cards_choose_ball') }}</p>

                <span v-if="drawing" class="sr-only">{{ t('ui.cards_cranking') }}</span>

                <template v-if="result">
                    <template v-if="result.kind === 'sticker'">
                        <p class="mb-3 text-center font-serif text-lg text-accent">
                            {{ t('ui.cards_sticker_new') }}
                        </p>
                        <div class="flex flex-col items-center gap-3">
                            <LibrarySticker :sticker="result.sticker.key" :size="170" />
                            <p class="text-center font-serif text-lg text-ink">{{ result.sticker.name }}</p>
                        </div>
                    </template>
                    <template v-else>
                        <p class="mb-3 text-center font-serif text-lg text-accent">
                            {{ t('ui.cards_new_card') }}
                        </p>
                        <CardEnvelope :card="result.card" @open="emit('open')" />
                    </template>
                    <p v-if="countdown" class="mt-4 text-center text-sm text-ink-muted">
                        {{ t('ui.cards_reset_in', { time: countdownText }) }}
                    </p>
                </template>
            </div>
        </template>
    </section>
</template>
```

- [ ] **Step 6: Ejecutar la prueba**

Run: `npx vitest run resources/js/Components/__tests__/CardMachine.test.js`

Expected: PASS.

- [ ] **Step 7: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 8: Página `/cards` con estado local de stickers y completo

**Files:**
- Modify: `resources/js/Pages/Cards/Index.vue`

- [ ] **Step 1: Actualizar las props y el estado local**

Sustituye el `<script setup>` de `resources/js/Pages/Cards/Index.vue` por:

```vue
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardLetterModal from '@/Components/CardLetterModal.vue';
import CardMachine from '@/Components/CardMachine.vue';
import { useI18n } from '@/i18n';
import { Head, Link } from '@inertiajs/vue3';
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
    stickers: { type: Array, required: true },
    stickerProgress: { type: Object, required: true },
    complete: { type: Boolean, default: false },
});

const { t } = useI18n();
const cards = ref(props.cards);
const stickers = ref(props.stickers);
const stickerProgress = ref({ ...props.stickerProgress });
const ink = ref(props.ink);
const drawing = ref(false);
const result = ref(props.drawn);
const letter = ref(null);
const letterOpen = ref(false);
const error = ref('');

async function crank(index) {
    if (drawing.value) {
        return;
    }

    drawing.value = true;
    error.value = '';

    try {
        const { data } = await axios.post('/cards/draw', { ball: index });
        result.value = data;
        ink.value = data.ink;

        if (data.kind === 'card' && data.card) {
            const card = cards.value.find((item) => item.key === data.card.key);

            if (card) {
                card.unlocked = true;
                card.copies = 1;
            }
        }

        if (data.kind === 'sticker' && data.sticker) {
            const sticker = stickers.value.find((item) => item.key === data.sticker.key);

            if (sticker) {
                sticker.unlocked = true;
                sticker.acquired_at = new Date().toISOString();
            }

            stickerProgress.value.unlocked += 1;
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

function openResult() {
    if (result.value?.card) {
        openLetter(result.value.card.key);
    }
}
</script>
```

- [ ] **Step 2: Actualizar el template**

Sustituye el `<template>` por:

```vue
<template>
    <Head :title="t('ui.cards_title')" />

    <AuthenticatedLayout>
        <p
            v-if="error"
            class="mb-4 rounded-control border border-danger/40 bg-danger/10 px-3 py-2 text-danger"
            role="alert"
        >
            {{ error }}
        </p>

        <p
            v-if="progress.total === 0 && !complete"
            class="mb-4 rounded-control border border-edge bg-surface px-3 py-2 text-sm text-ink-muted"
        >
            {{ t('ui.cards_empty_help') }}
            <Link href="/books/create" class="text-accent underline">{{ t('ui.add_book') }}</Link>
        </p>

        <div class="mx-auto w-full max-w-md">
            <CardMachine
                :offers="offers"
                :drawing="drawing"
                :result="result"
                :reset-at="resetAt"
                :complete="complete"
                @crank="crank"
                @open="openResult"
            />

            <Link
                href="/cards/album"
                class="mt-6 flex min-h-11 items-center justify-center rounded-control border border-dorado/60 px-4 py-2 text-center text-sm text-accent hover:bg-surface"
            >
                {{ t('ui.cards_open_album') }}
            </Link>
        </div>

        <CardLetterModal
            :show="letterOpen"
            :letter="letter"
            :copies="letter?.copies ?? 0"
            :duplicate="letter?.duplicate ?? false"
            @close="letterOpen = false"
        />
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 3: Ejecutar las pruebas de frontend**

Run: `npm run test -- --run`

Expected: PASS. Si falla alguna prueba por el estado completo, revisa que `CardMachine` tenga la prop `complete`.

- [ ] **Step 4: Compilar**

Run: `npm run build`

Expected: build correcto.

- [ ] **Step 5: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 9: Sección de stickers en el álbum

**Files:**
- Modify: `resources/js/Components/CardAlbum.vue`
- Modify: `resources/js/Pages/Cards/Album.vue`
- Modify: `resources/js/Components/__tests__/CardAlbum.test.js`

- [ ] **Step 1: Actualizar la prueba del álbum**

Sustituye `resources/js/Components/__tests__/CardAlbum.test.js` por:

```js
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import CardAlbum from '../CardAlbum.vue';

enableAutoUnmount(afterEach);

const cards = [
    {
        key: 'violet',
        family: 'empireo',
        familyLabel: 'Empíreo',
        familyEmblem: 'dragon',
        character: 'Violet Sorrengail',
        monogram: 'VS',
        emblem: 'dragon',
        unlocked: true,
        copies: 1,
    },
    {
        key: 'grace',
        family: 'mapa',
        familyLabel: 'El mapa de los anhelos',
        familyEmblem: 'map',
        character: 'Grace Peterson',
        monogram: 'GP',
        emblem: 'map',
        unlocked: false,
        copies: 0,
    },
];

const stickers = [
    { key: 'maquina_libro', name: 'Libro abierto', unlocked: true, acquired_at: '2026-11-10T12:00:00Z' },
    { key: 'maquina_pluma', name: 'Pluma de ganso', unlocked: false, acquired_at: null },
];

function mountAlbum(overrides = {}) {
    return mount(CardAlbum, {
        props: {
            cards,
            progress: { unlocked: 1, total: 2 },
            stickers,
            stickerProgress: { unlocked: 1, total: 2 },
            ...overrides,
        },
    });
}

describe('CardAlbum', () => {
    it('muestra una ranura por carta con su progreso', () => {
        const wrapper = mountAlbum();

        expect(wrapper.findAll('.card-slot')).toHaveLength(2);
        expect(wrapper.text()).toContain('1 de 2 cartas');
    });

    it('deja las cartas bloqueadas con interrogante y sin botón', () => {
        const wrapper = mountAlbum();

        const locked = wrapper.findAll('.card-slot')[1];

        expect(locked.element.tagName).toBe('DIV');
        expect(locked.text()).toContain('?');
    });

    it('abre la carta desbloqueada con su clave', async () => {
        const wrapper = mountAlbum();

        await wrapper.findAll('.card-slot')[0].trigger('click');

        expect(wrapper.emitted('open')).toEqual([['violet']]);
    });

    it('muestra la sección de stickers con su progreso', () => {
        const wrapper = mountAlbum();

        expect(wrapper.text()).toContain('Stickers de la máquina');
        expect(wrapper.text()).toContain('1 de 2 stickers');
        expect(wrapper.findAll('.sticker-slot')).toHaveLength(2);
        expect(wrapper.findAll('.library-sticker')).toHaveLength(2);
        expect(wrapper.text()).toContain('Libro abierto');
        expect(wrapper.text()).toContain('Bloqueado');
    });
});
```

- [ ] **Step 2: Ejecutar la prueba y comprobar que falla**

Run: `npx vitest run resources/js/Components/__tests__/CardAlbum.test.js`

Expected: FAIL porque `CardAlbum` no acepta `stickers` ni `stickerProgress`.

- [ ] **Step 3: Añadir la sección de stickers a `CardAlbum`**

Sustituye `resources/js/Components/CardAlbum.vue` por:

```vue
<script setup>
import CardEmblem from './CardEmblem.vue';
import LibrarySticker from './LibrarySticker.vue';
import { useI18n } from '@/i18n';

defineProps({
    cards: { type: Array, required: true },
    progress: { type: Object, required: true },
    stickers: { type: Array, required: true },
    stickerProgress: { type: Object, required: true },
});

defineEmits(['open']);

const { t } = useI18n();
</script>

<template>
    <section class="card-album" :aria-label="t('ui.cards_album')">
        <header class="mb-4">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-serif text-xl text-accent">{{ t('ui.cards_album') }}</h2>
                <span class="text-sm text-ink-muted">
                    {{ t('ui.cards_album_progress', { unlocked: progress.unlocked, total: progress.total }) }}
                </span>
            </div>
            <div
                class="mt-2 h-2 rounded-full bg-edge"
                role="progressbar"
                :aria-valuenow="progress.unlocked"
                :aria-valuemin="0"
                :aria-valuemax="progress.total"
                :aria-label="t('ui.cards_album')"
            >
                <div
                    class="h-full rounded-full bg-accent transition-all"
                    :style="{ width: `${progress.total ? (progress.unlocked / progress.total) * 100 : 0}%` }"
                ></div>
            </div>
        </header>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <template v-for="card in cards" :key="card.key">
                <button
                    v-if="card.unlocked"
                    type="button"
                    class="card-slot flex min-h-40 flex-col items-center justify-center gap-2 p-3 text-center text-crema transition hover:ring-2 hover:ring-accent/60"
                    @click="$emit('open', card.key)"
                >
                    <CardEmblem :name="card.emblem" :size="26" class="text-dorado" />
                    <span class="card-monogram h-10 w-10 text-sm">{{ card.monogram }}</span>
                    <span class="text-sm leading-tight">{{ card.character }}</span>
                    <span class="text-xs text-crema/70">{{ card.familyLabel }}</span>
                    <span v-if="card.copies > 1" class="font-serif text-sm text-dorado">
                        {{ t('ui.cards_copies', { count: card.copies }) }}
                    </span>
                </button>

                <div
                    v-else
                    class="card-slot card-back min-h-40 flex-col gap-2 p-3 text-center"
                    aria-disabled="true"
                    :title="t('ui.cards_locked_hint')"
                >
                    <CardEmblem :name="card.familyEmblem" :size="26" />
                    <span class="card-monogram h-10 w-10 text-lg" aria-hidden="true">?</span>
                    <span class="text-sm text-crema/70">{{ t('ui.cards_locked') }}</span>
                    <span class="sr-only">{{ t('ui.cards_locked_hint') }}</span>
                </div>
            </template>
        </div>

        <footer class="mt-4 text-center text-xs text-ink-muted">
            {{ t('ui.cards_disclaimer') }}
        </footer>

        <section class="mt-8" :aria-label="t('ui.cards_machine_stickers')">
            <header class="mb-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="font-serif text-xl text-accent">{{ t('ui.cards_machine_stickers') }}</h2>
                    <span class="text-sm text-ink-muted">
                        {{ t('ui.cards_sticker_progress', { unlocked: stickerProgress.unlocked, total: stickerProgress.total }) }}
                    </span>
                </div>
                <div
                    class="mt-2 h-2 rounded-full bg-edge"
                    role="progressbar"
                    :aria-valuenow="stickerProgress.unlocked"
                    :aria-valuemin="0"
                    :aria-valuemax="stickerProgress.total"
                    :aria-label="t('ui.cards_machine_stickers')"
                >
                    <div
                        class="h-full rounded-full bg-accent transition-all"
                        :style="{ width: `${stickerProgress.total ? (stickerProgress.unlocked / stickerProgress.total) * 100 : 0}%` }"
                    ></div>
                </div>
            </header>

            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                <div
                    v-for="sticker in stickers"
                    :key="sticker.key"
                    class="sticker-slot flex flex-col items-center gap-1 rounded-card border border-edge bg-surface/80 p-2 text-center"
                >
                    <LibrarySticker :sticker="sticker.key" :size="96" :locked="!sticker.unlocked" />
                    <span class="text-xs leading-tight text-ink">
                        {{ sticker.unlocked ? sticker.name : t('ui.sticker_locked') }}
                    </span>
                </div>
            </div>
        </section>
    </section>
</template>
```

- [ ] **Step 4: Pasar las props desde `Cards/Album.vue`**

Sustituye `resources/js/Pages/Cards/Album.vue` por:

```vue
<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardAlbum from '@/Components/CardAlbum.vue';
import { useI18n } from '@/i18n';

defineProps({
    cards: { type: Array, required: true },
    progress: { type: Object, required: true },
    ink: { type: Number, default: 0 },
    stickers: { type: Array, required: true },
    stickerProgress: { type: Object, required: true },
});

const { t } = useI18n();
</script>

<template>
    <Head :title="t('ui.cards_album')" />

    <AuthenticatedLayout>
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <h1 class="font-serif text-3xl text-accent">{{ t('ui.cards_album') }}</h1>
            <div class="card-ink" :title="t('ui.cards_ink_help')">
                <span class="text-sm text-ink-muted">{{ t('ui.cards_ink') }}</span>
                <strong class="text-2xl text-accent">{{ ink }}</strong>
                <span class="sr-only">{{ t('ui.cards_ink_help') }}</span>
            </div>
        </header>

        <CardAlbum
            :cards="cards"
            :progress="progress"
            :stickers="stickers"
            :sticker-progress="stickerProgress"
        />
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 5: Ejecutar las pruebas de frontend**

Run: `npx vitest run resources/js/Components/__tests__/CardAlbum.test.js`

Expected: PASS.

- [ ] **Step 6: Compilar**

Run: `npm run build`

Expected: build correcto.

- [ ] **Step 7: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 10: Limpieza de textos y documentación visual

**Files:**
- Modify: `lang/es.json`
- Modify: `lang/en.json`
- Modify: `docs/DISENO.md`

- [ ] **Step 1: Limpiar textos obsoletos**

En `lang/es.json` y `lang/en.json`:

- Elimina `ui.cards_duplicate`.
- Sustituye `ui.cards_ink_help` por:
  - Español: `"La tinta se gana con los eventos."`
  - Inglés: `"Ink is earned through events."`

- [ ] **Step 2: Actualizar la sección de la máquina en `docs/DISENO.md`**

En `### Máquina expendedora (Cards/Index)`:

- Cambia la frase del sobre que dice que una carta repetida avisa `+1 tinta` por: el sobre solo aparece cuando la bolita contiene una carta nueva; no hay cartas repetidas.
- Añade un punto **Stickers de la máquina (`LibrarySticker`)** con: 20 objetos de biblioteca, silueta blanca troquelada, sombra, versión bloqueada con `?`, y resultado de sticker con nombre y mensaje `ui.cards_sticker_new`.
- Añade un punto **Colección completa** con: cuando no quedan cartas ni stickers, la máquina muestra `ui.cards_complete` y `ui.cards_complete_help` y oculta la manivela.
- En **Álbum (`CardAlbum`)**, añade la sección **Stickers de la máquina** con rejilla de 3 columnas a 360 px, 4 en tablet y 5-6 en escritorio, progreso propio y ranuras `.sticker-slot`.
- En **Estados**, sustituye la página vacía por: sin libros la máquina sigue disponible solo con stickers; el aviso invita a añadir libros para desbloquear cartas.
- En **Clases nuevas**, añade `library-sticker`, `library-sticker__svg`, `library-sticker__cut`, `library-sticker__art`, `library-sticker__locked`, `library-sticker--locked` y `sticker-slot`. Indica que no se añaden colores nuevos.

- [ ] **Step 3: Añadir la fila al registro de cambios**

Al final de la tabla `## 13. Registro de cambios`, añade:

```markdown
| 2026-11-10 | La máquina expendedora deja de repetir cartas y añade una colección de **20 stickers de objetos de biblioteca** (`LibrarySticker`) con borde blanco troquelado. Las ocho bolitas son sorpresa y mezclan cartas y stickers no obtenidos; al completar las 40 piezas la máquina se detiene con el estado **¡Colección completa!**. El álbum de cartas añade la sección **Stickers de la máquina** con progreso propio. Clases `library-sticker*` y `sticker-slot`; sin colores nuevos. | Agente |
```

- [ ] **Step 4: Ejecutar la prueba de catálogos**

Run: `php artisan test --filter=CatalogTest`

Expected: PASS. Los catálogos siguen planos y con las mismas claves en español e inglés.

- [ ] **Step 5: Revisar el diff y avisar**

Run: `git diff --stat`

No commit.

---

## Task 11: Verificación final

**Files:** ninguno nuevo.

- [ ] **Step 1: Formatear PHP**

Run: `php vendor\bin\pint app\Models\CardDraw.php app\Services\CardCatalogService.php app\Services\CardDeckService.php app\Services\EventService.php app\Http\Controllers\CardController.php app\Http\Controllers\EventController.php database\migrations\2026_11_10_000000_add_kind_to_card_draws_table.php tests\Feature\CardDrawModelTest.php tests\Feature\CardDrawTest.php tests\Feature\CardPageTest.php tests\Feature\CardCatalogTest.php tests\Feature\EventAlbumTest.php tests\Feature\EventPageTest.php`

Expected: sin cambios de formato pendientes.

- [ ] **Step 2: Ejecutar todas las pruebas de backend**

Run: `php artisan test`

Expected: PASS. Deben pasar las pruebas de cartas, eventos, catálogos y ajustes.

- [ ] **Step 3: Ejecutar todas las pruebas de frontend**

Run: `npm run test -- --run`

Expected: PASS.

- [ ] **Step 4: Compilar producción**

Run: `npm run build`

Expected: build correcto.

- [ ] **Step 5: Revisar responsive**

Comprobar manualmente a 360 px y escritorio:

- La máquina y el botón **Ver álbum** no se desbordan.
- Las bolitas se ven iguales y la manivela responde.
- El resultado de sticker se ve completo con su nombre.
- La sección de stickers del álbum usa 3 columnas a 360 px y no corta nombres.
- El estado **¡Colección completa!** se lee bien.

- [ ] **Step 6: Revisar el diff final y avisar**

Run: `git status --short` y `git diff --stat`

No commit. Informa a la usuaria de los archivos modificados y deja que ella haga el commit.

---

## Autorevisión del plan

- **Cobertura del spec:** persistencia (`Task 1`), catálogo y textos (`Task 2`), mezcla y tirada (`Task 3`), props y resultado persistido (`Task 4`), aislamiento de eventos (`Task 5`), componente de stickers (`Task 6`), resultado y completo (`Task 7`), página de la máquina (`Task 8`), álbum (`Task 9`), documentación (`Task 10`), verificación (`Task 11`).
- **Sin repetidos:** las ofertas solo usan objetos no obtenidos y las escrituras usan `firstOrCreate` + comprobación de `wasRecentlyCreated`.
- **Sin commits:** todas las tareas terminan revisando el diff y dejando el commit a la usuaria.
