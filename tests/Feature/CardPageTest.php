<?php

use App\Models\Book;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserWallet;
use App\Services\CardDeckService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de sangre',
        'author' => 'Rebecca Yarros',
    ]);
});

it('carga la página de cartas con el pool y las balotas', function () {
    $this->get(route('cards.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cards/Index')
            ->has('cards', 9)
            ->has('offers', 8)
            ->where('progress.total', 9)
            ->where('progress.unlocked', 0)
            ->where('drawn', null)
            ->where('ink', 0)
        );
});

it('no expone la clave ni el personaje en las balotas', function () {
    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('offers.0.key')
            ->missing('offers.0.character')
            ->missing('offers.0.emblem')
        );
});

it('devuelve la carta desbloqueada en el idioma activo', function () {
    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => 'violet',
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);

    $this->getJson(route('cards.show', 'violet'))
        ->assertOk()
        ->assertJsonPath('card.key', 'violet')
        ->assertJsonPath('card.character', 'Violet Sorrengail')
        ->assertJsonPath('copies', 1);
});

it('prohíbe abrir una carta que no está desbloqueada', function () {
    $this->getJson(route('cards.show', 'violet'))->assertForbidden();
});

it('devuelve 404 para una carta desconocida', function () {
    $this->getJson(route('cards.show', 'no_existe'))->assertNotFound();
});

it('tira una vez al día y la segunda responde 422', function () {
    config(['cards.allow_multiple_draws' => false]);

    $this->postJson(route('cards.draw'), ['ball' => 0])
        ->assertOk()
        ->assertJsonStructure(['card', 'duplicate', 'copies', 'ink', 'inkEarned', 'resetAt']);

    $this->postJson(route('cards.draw'), ['ball' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('ball');
});

it('guarda el resultado y la tinta de un repetido', function () {
    $offers = app(CardDeckService::class)->offersFor($this->user);

    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => $offers[0],
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);
    UserWallet::create(['user_id' => $this->user->id, 'ink' => 0]);

    $this->postJson(route('cards.draw'), ['ball' => 0])
        ->assertOk()
        ->assertJsonPath('duplicate', true)
        ->assertJsonPath('copies', 2)
        ->assertJsonPath('ink', 1);
});

it('deja la máquina activa tras tirar en modo de pruebas', function () {
    config(['cards.allow_multiple_draws' => true]);

    $this->postJson(route('cards.draw'), ['ball' => 0])->assertOk();
    $this->postJson(route('cards.draw'), ['ball' => 1])->assertOk();

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('drawn', null)
        );
});

it('no mezcla datos de otras usuarias', function () {
    $other = User::factory()->create();
    Book::factory()->for($other)->create([
        'title' => 'El mapa de los anhelos',
        'author' => 'Alice Kellen',
    ]);
    UserCard::create([
        'user_id' => $other->id,
        'card_key' => 'grace',
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cards', 9)
            ->where('progress.unlocked', 0)
        );

    $this->getJson(route('cards.show', 'grace'))->assertForbidden();
});
