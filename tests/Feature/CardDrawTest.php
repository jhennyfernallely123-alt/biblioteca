<?php

use App\Models\Book;
use App\Models\CardDraw;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserWallet;
use App\Services\CardDeckService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

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
    config(['cards.allow_multiple_draws' => false]);
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

it('permite repetir la tirada en modo de pruebas', function () {
    config(['cards.allow_multiple_draws' => true]);
    giveEmpyreanBooks($this->user);
    $deck = app(CardDeckService::class);
    $offers = $deck->offersFor($this->user);

    $first = $deck->draw($this->user, 0);
    $second = $deck->draw($this->user, 0);

    expect($first['card']['key'])->toBe($offers[0])
        ->and($first['duplicate'])->toBeFalse()
        ->and($second['card']['key'])->toBe($offers[0])
        ->and($second['duplicate'])->toBeTrue()
        ->and($second['copies'])->toBe(2)
        ->and($second['ink'])->toBe(1);
});
