<?php

use App\Models\EventProgress;
use App\Models\User;
use App\Models\UserCollectible;
use App\Services\EventService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    config(['events.timezone' => 'UTC']);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-05 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->service = app(EventService::class);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('registra una tarea una vez por día y otorga tinta', function () {
    $this->service->completeTask($this->user, 'draw');
    $this->service->completeTask($this->user, 'draw');

    expect($this->user->fresh()->wallet->ink)->toBe(2)
        ->and($this->service->todayProgress($this->user))->toHaveCount(1)
        ->and($this->user->eventProgress()->count())->toBe(1);
});

it('otorga la tinta de cada tarea del evento activo', function () {
    $this->service->completeTask($this->user, 'draw');
    $this->service->completeTask($this->user, 'read');

    expect($this->user->fresh()->wallet->ink)->toBe(5);
});

it('ignora tareas que el evento activo no pide', function () {
    $this->service->completeTask($this->user, 'add_book');

    expect($this->user->eventProgress()->count())->toBe(0)
        ->and($this->user->fresh()->wallet?->ink ?? 0)->toBe(0);
});

it('guarda el día del evento activo', function () {
    $this->service->completeTask($this->user, 'draw');

    $row = EventProgress::first();

    expect($row->event_key)->toBe('feria_libro')
        ->and($row->day_date->toDateString())->toBe(now(config('events.timezone'))->toDateString())
        ->and($row->task_key)->toBe('draw')
        ->and($row->ink_earned)->toBe(2);
});

it('nunca rompe la acción del usuario', function () {
    config(['events.catalog' => []]);

    $this->service->completeTask($this->user, 'draw');

    expect(true)->toBeTrue();
});

it('relaciona coleccionables con la usuaria', function () {
    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'feria_pluma',
        'kind' => 'sticker',
        'event_key' => 'feria_libro',
        'source' => 'shop',
        'acquired_at' => now(),
    ]);

    expect($this->user->collectibles()->count())->toBe(1)
        ->and($this->user->collectibles()->first()->collectible_key)->toBe('feria_pluma');
});
