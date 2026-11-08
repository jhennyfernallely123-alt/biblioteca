<?php

use App\Models\User;
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

it('muestra el evento activo con tareas y tienda', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->has('event')
            ->has('tasks', 3)
            ->has('shop', 3)
            ->has('collection', 0)
            ->where('event.key', 'feria_libro')
            ->where('event.day', 1)
            ->where('event.days', 3)
        );
});
