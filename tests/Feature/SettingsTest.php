<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('actualiza el nombre, el apodo y el correo del usuario', function () {
    $this->patch(route('settings.profile'), [
        'name' => 'Nuevo nombre',
        'nickname' => 'Nuevo apodo',
        'email' => 'nuevo@example.com',
        'default_view_mode' => 'spines',
    ])->assertRedirect();

    $user = $this->user->fresh();

    expect($user->name)->toBe('Nuevo nombre')
        ->and($user->nickname)->toBe('Nuevo apodo')
        ->and($user->email)->toBe('nuevo@example.com')
        ->and($user->default_view_mode)->toBe('spines');
});

it('valida el apodo al actualizar el perfil', function () {
    $this->patch(route('settings.profile'), [
        'name' => 'Nuevo nombre',
        'nickname' => '',
        'email' => 'nuevo@example.com',
        'default_view_mode' => 'spines',
    ])->assertSessionHasErrors('nickname');

    $this->patch(route('settings.profile'), [
        'name' => 'Nuevo nombre',
        'nickname' => str_repeat('a', 51),
        'email' => 'nuevo@example.com',
        'default_view_mode' => 'spines',
    ])->assertSessionHasErrors('nickname');
});

it('exige la contraseña actual para cambiarla', function () {
    $this->put(route('settings.password'), [
        'current_password' => 'incorrecta',
        'password' => 'nueva12345',
        'password_confirmation' => 'nueva12345',
    ])->assertSessionHasErrors('current_password');
});
