<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('aplica un idioma revisado', function () {
    $this->patch(route('settings.locale'), ['locale' => 'en'])
        ->assertRedirect();

    expect($this->user->fresh()->locale)->toBe('en');
});

it('rechaza un idioma no soportado', function () {
    $this->patch(route('settings.locale'), ['locale' => 'xx'])
        ->assertSessionHasErrors('locale');
});

it('prepara un idioma automático', function () {
    $this->post(route('settings.locale.prepare'), ['locale' => 'af'])
        ->assertOk()
        ->assertJsonPath('ready', false);
});
