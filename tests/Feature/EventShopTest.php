<?php

use App\Models\User;
use App\Services\EventService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    config(['events.timezone' => 'UTC']);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-05 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->service = app(EventService::class);
    $this->actingAs($this->user);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('compra una pegatina con tinta suficiente', function () {
    $this->user->wallet()->create(['ink' => 20]);

    $this->postJson(route('events.buy'), ['item' => 'feria_pluma'])
        ->assertOk()
        ->assertJsonPath('collectible.key', 'feria_pluma')
        ->assertJsonPath('ink', 5);

    expect($this->user->collectibles()->where('collectible_key', 'feria_pluma')->exists())->toBeTrue()
        ->and($this->user->fresh()->wallet->ink)->toBe(5);
});

it('rechaza sin tinta suficiente', function () {
    $this->user->wallet()->create(['ink' => 3]);

    $this->postJson(route('events.buy'), ['item' => 'feria_pluma'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('item');
});

it('rechaza duplicados', function () {
    $this->user->wallet()->create(['ink' => 40]);
    $this->service->grantCollectible($this->user, 'feria_pluma', 'shop');

    $this->postJson(route('events.buy'), ['item' => 'feria_pluma'])
        ->assertStatus(409);
});

it('rechaza artículos de otros eventos', function () {
    $this->user->wallet()->create(['ink' => 100]);

    $this->postJson(route('events.buy'), ['item' => 'brujas_calavera'])
        ->assertStatus(422);
});
