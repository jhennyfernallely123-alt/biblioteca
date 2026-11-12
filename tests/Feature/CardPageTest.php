<?php

use App\Models\Book;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserCollectible;
use App\Services\CardDeckService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['events.enabled' => false]);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Book::factory()->for($this->user)->create([
        'title' => 'Alas de sangre',
        'author' => 'Rebecca Yarros',
    ]);
});

it('carga la página de cartas con cartas, stickers y bolitas', function () {
    $this->get(route('cards.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cards/Index')
            ->has('cards', 9)
            ->has('offers', 8)
            ->has('stickers', 20)
            ->where('progress.total', 9)
            ->where('progress.unlocked', 0)
            ->where('stickerProgress.total', 20)
            ->where('stickerProgress.unlocked', 0)
            ->where('complete', false)
            ->where('drawn', null)
            ->where('ink', 0)
        );
});

it('no expone el tipo ni la clave en las bolitas', function () {
    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('offers.0.key')
            ->missing('offers.0.kind')
            ->missing('offers.0.family')
            ->missing('offers.0.character')
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
        ->assertJsonStructure(['kind', 'card', 'sticker', 'ink', 'resetAt']);

    $this->postJson(route('cards.draw'), ['ball' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('ball');
});

it('guarda una carta nueva sin tinta', function () {
    $offers = app(CardDeckService::class)->offersFor($this->user);
    $index = collect($offers)->search(fn (array $offer) => $offer['kind'] === 'card');

    $this->postJson(route('cards.draw'), ['ball' => $index])
        ->assertOk()
        ->assertJsonPath('kind', 'card')
        ->assertJsonPath('sticker', null)
        ->assertJsonPath('ink', 0);
});

it('guarda un sticker nuevo y lo muestra al recargar', function () {
    config(['cards.allow_multiple_draws' => false]);

    $offers = app(CardDeckService::class)->offersFor($this->user);
    $index = collect($offers)->search(fn (array $offer) => $offer['kind'] === 'sticker');
    $key = $offers[$index]['key'];

    $this->postJson(route('cards.draw'), ['ball' => $index])
        ->assertOk()
        ->assertJsonPath('kind', 'sticker')
        ->assertJsonPath('sticker.key', $key)
        ->assertJsonPath('card', null);

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('drawn.kind', 'sticker')
            ->where('drawn.sticker.key', $key)
            ->where('drawn.card', null)
            ->where('stickerProgress.unlocked', 1)
        );
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

it('funciona sin libros ofreciendo solo stickers', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cards', 0)
            ->has('offers', 8)
            ->where('progress.total', 0)
            ->where('complete', false)
        );
});

it('marca la colección completa cuando ya no queda nada', function () {
    foreach (array_keys(config('cards.cards')) as $key) {
        UserCard::create([
            'user_id' => $this->user->id,
            'card_key' => $key,
            'copies' => 1,
            'first_unlocked_at' => now(),
        ]);
    }

    foreach (config('cards.machine_stickers') as $sticker) {
        UserCollectible::create([
            'user_id' => $this->user->id,
            'collectible_key' => $sticker['key'],
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
            'acquired_at' => now(),
        ]);
    }

    Book::factory()->for($this->user)->create([
        'title' => 'El mapa de los anhelos',
        'author' => 'Alice Kellen',
    ]);

    $this->get(route('cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('offers', 0)
            ->where('complete', true)
        );
});

it('carga el álbum de cartas y stickers como página propia', function () {
    UserCard::create([
        'user_id' => $this->user->id,
        'card_key' => 'violet',
        'copies' => 1,
        'first_unlocked_at' => now(),
    ]);

    UserCollectible::create([
        'user_id' => $this->user->id,
        'collectible_key' => 'maquina_libro',
        'kind' => 'sticker',
        'event_key' => 'maquina',
        'source' => 'machine',
        'acquired_at' => now(),
    ]);

    $this->get(route('cards.album'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cards/Album')
            ->has('cards', 9)
            ->has('stickers', 20)
            ->where('progress.unlocked', 1)
            ->where('progress.total', 9)
            ->where('stickerProgress.unlocked', 1)
            ->where('stickerProgress.total', 20)
            ->where('ink', 0)
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
