<?php

use App\Models\User;

it('usa español por defecto', function () {
    expect((new User)->locale)->toBe('es');
});

it('aplica el idioma de la usuaria autenticada', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('direction', 'ltr')
            ->where('translations', fn ($translations) => $translations['ui.nav_library'] === 'Library'));
});

it('usa español para invitados', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'es'));
});

it('cae en español si el idioma guardado no está soportado', function () {
    $user = User::factory()->create(['locale' => 'xx']);

    $this->actingAs($user)
        ->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'es'));
});
