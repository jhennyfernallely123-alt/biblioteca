<?php

use App\Models\User;

it('crea las estanterías iniciales al registrarse', function () {
    $response = $this->post('/register', [
        'name' => 'Ana',
        'nickname' => 'Anita',
        'email' => 'ana@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'ana@example.com')->firstOrFail();

    expect($user->nickname)->toBe('Anita')
        ->and($user->shelves()->pluck('name')->all())
        ->toBe(['Pendientes', 'Leyendo', 'Leídos']);
});

it('muestra los errores de registro en español', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->post('/register', [
        'name' => 'Ana',
        'nickname' => 'Anita',
        'email' => 'ana@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors([
        'email' => 'el correo electrónico ya está en uso.',
    ]);
});

it('exige un apodo al registrarse', function () {
    $this->post('/register', [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('nickname');
});
