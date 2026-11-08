<?php

use App\Services\EventService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    config(['events.timezone' => 'UTC']);
    $this->service = app(EventService::class);
});

it('empieza con el primer evento en la época', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-05', 'UTC'));

    $active = $this->service->activeEvent();

    expect($active['event']['key'])->toBe('feria_libro')
        ->and($active['day'])->toBe(1)
        ->and($active['days'])->toBe(3);

    CarbonImmutable::setTestNow();
});

it('avanza al segundo evento cuando el primero termina', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-08', 'UTC'));

    expect($this->service->activeEvent()['event']['key'])->toBe('noche_dragones')
        ->and($this->service->activeEvent()['day'])->toBe(1);

    CarbonImmutable::setTestNow();
});

it('recorre el ciclo completo y repite', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-21', 'UTC'));

    expect($this->service->activeEvent()['event']['key'])->toBe('feria_libro')
        ->and($this->service->activeEvent()['day'])->toBe(1);

    CarbonImmutable::setTestNow();
});

it('respeta el último día de un evento', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-07', 'UTC'));

    expect($this->service->activeEvent()['event']['key'])->toBe('feria_libro')
        ->and($this->service->activeEvent()['day'])->toBe(3);

    CarbonImmutable::setTestNow();
});
