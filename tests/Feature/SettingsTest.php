<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('actualiza el nombre y el correo del usuario', function () {
    $this->patch(route('settings.profile'), [
        'name' => 'Nuevo nombre',
        'email' => 'nuevo@example.com',
        'default_view_mode' => 'spines',
    ])->assertRedirect();

    $user = $this->user->fresh();

    expect($user->name)->toBe('Nuevo nombre')
        ->and($user->email)->toBe('nuevo@example.com')
        ->and($user->default_view_mode)->toBe('spines');
});

it('exige la contraseña actual para cambiarla', function () {
    $this->put(route('settings.password'), [
        'current_password' => 'incorrecta',
        'password' => 'nueva12345',
        'password_confirmation' => 'nueva12345',
    ])->assertSessionHasErrors('current_password');
});
