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
