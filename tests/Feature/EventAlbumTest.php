<?php

use App\Models\User;
use App\Models\UserCollectible;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['events.timezone' => 'UTC']);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-05 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('muestra las 12 pegatinas con el progreso', function () {
    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'feria_pluma',
        'kind' => 'sticker',
        'event_key' => 'feria_libro',
        'source' => 'shop',
        'acquired_at' => now(),
    ]);

    $this->get(route('events.album'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Album')
            ->has('stickers', 12)
            ->where('progress.owned', 1)
            ->where('progress.total', 12)
            ->where('progress.by_rarity.comun', 1)
            ->where('progress.by_rarity.rara', 0)
            ->where('progress.by_rarity.epica', 0)
            ->where('stickers.0.key', 'feria_pluma')
            ->where('stickers.0.rarity', 'comun')
            ->where('stickers.0.owned', true)
            ->where('stickers.0.acquired_at', fn ($value) => is_string($value) && $value !== '')
            ->where('stickers.1.owned', false)
            ->where('stickers.1.acquired_at', null)
        );
});

it('desglosa el progreso por rareza', function () {
    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'feria_pluma',
        'kind' => 'sticker',
        'event_key' => 'feria_libro',
        'source' => 'shop',
        'acquired_at' => now(),
    ]);
    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'dragones_escama',
        'kind' => 'sticker',
        'event_key' => 'noche_dragones',
        'source' => 'shop',
        'acquired_at' => now(),
    ]);
    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'feria_lampara',
        'kind' => 'sticker',
        'event_key' => 'feria_libro',
        'source' => 'shop',
        'acquired_at' => now(),
    ]);

    $this->get(route('events.album'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('progress.owned', 3)
            ->where('progress.total', 12)
            ->where('progress.by_rarity.comun', 1)
            ->where('progress.by_rarity.rara', 1)
            ->where('progress.by_rarity.epica', 1)
        );
});

it('no mezcla las pegatinas de otras usuarias', function () {
    $other = User::factory()->create();

    UserCollectible::create([
        'user_id' => $other->id,
        'collectible_key' => 'feria_pluma',
        'kind' => 'sticker',
        'event_key' => 'feria_libro',
        'source' => 'shop',
        'acquired_at' => now(),
    ]);

    $this->get(route('events.album'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('progress.owned', 0)
            ->where('stickers.0.owned', false)
        );
});
