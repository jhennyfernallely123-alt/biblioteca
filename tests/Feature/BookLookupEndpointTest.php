<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('devuelve resultados de búsqueda para el usuario autenticado', function () {
    $this->actingAs(User::factory()->create());

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [['volumeInfo' => ['title' => 'Rayuela', 'authors' => ['Cortázar']]]],
        ]),
    ]);

    $this->getJson(route('books.lookup', ['q' => 'rayuela']))
        ->assertOk()
        ->assertJsonPath('results.0.title', 'Rayuela');
});

it('rechaza isbn inválido con 422', function () {
    $this->actingAs(User::factory()->create());

    $this->getJson(route('books.lookup', ['isbn' => '123']))
        ->assertStatus(422);
});
