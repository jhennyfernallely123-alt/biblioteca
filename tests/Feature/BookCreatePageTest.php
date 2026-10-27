<?php

use App\Models\Shelf;
use App\Models\User;

it('muestra el formulario de alta con las estanterías del usuario', function () {
    $user = User::factory()->create();
    Shelf::factory()->for($user)->create(['name' => 'Pendientes']);

    $this->actingAs($user)
        ->get(route('books.create'))
        ->assertOk()
        ->assertSee('Pendientes');
});
