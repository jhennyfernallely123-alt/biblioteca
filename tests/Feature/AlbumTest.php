<?php

use App\Models\Book;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserCollectible;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_solo_objetos_del_usuario_autenticado(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Book::factory()->for($user)->create([
            'title' => 'Alas de sangre',
            'author' => 'Rebecca Yarros',
        ]);
        Book::factory()->for($other)->create([
            'title' => 'Perfectos mentirosos',
            'author' => 'Álex Ramírez',
        ]);

        UserCard::create([
            'user_id' => $user->id,
            'card_key' => 'violet',
            'copies' => 1,
            'first_unlocked_at' => now(),
        ]);
        UserCollectible::create([
            'user_id' => $user->id,
            'collectible_key' => 'maquina_libro',
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
            'acquired_at' => now(),
        ]);
        UserCard::create([
            'user_id' => $other->id,
            'card_key' => 'alex',
            'copies' => 1,
            'first_unlocked_at' => now(),
        ]);
        UserCollectible::create([
            'user_id' => $other->id,
            'collectible_key' => 'maquina_pluma',
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
            'acquired_at' => now(),
        ]);

        $this->actingAs($user)->get('/album')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Album/Index')
                ->has('items')
                ->where('items', fn ($items) => collect($items)->contains(fn ($item) => $item['key'] === 'violet' && $item['unlocked'] === true))
                ->where('items', fn ($items) => ! collect($items)->contains(fn ($item) => $item['key'] === 'alex'))
                ->where('items', fn ($items) => collect($items)->contains(fn ($item) => $item['key'] === 'maquina_libro' && $item['unlocked'] === true))
                ->where('items', fn ($items) => collect($items)->contains(fn ($item) => $item['key'] === 'maquina_pluma' && $item['unlocked'] === false))
            );
    }

    public function test_progreso_coincide_con_cartas_y_stickers_obtenidos(): void
    {
        $user = User::factory()->create();

        Book::factory()->for($user)->create([
            'title' => 'Alas de sangre',
            'author' => 'Rebecca Yarros',
        ]);
        Book::factory()->for($user)->create([
            'title' => 'El mapa de los anhelos',
            'author' => 'Alice Kellen',
        ]);
        Book::factory()->for($user)->create([
            'title' => 'La biblioteca de la medianoche',
            'author' => 'Matt Haig',
        ]);
        Book::factory()->for($user)->create([
            'title' => 'Perfectos mentirosos',
            'author' => 'Álex Ramírez',
        ]);

        $this->actingAs($user)->get('/album')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Album/Index')
                ->where('progress.unlocked', 0)
                ->where('progress.total', 40)
                ->where('progress.byRarity.comun', 0)
                ->where('progress.byRarity.rara', 0)
                ->where('progress.byRarity.epica', 0)
            );
    }

    public function test_desbloquea_cartas_y_stickers_propios_al_coleccionarlos(): void
    {
        $user = User::factory()->create();

        Book::factory()->for($user)->create([
            'title' => 'Alas de sangre',
            'author' => 'Rebecca Yarros',
        ]);

        UserCard::create([
            'user_id' => $user->id,
            'card_key' => 'violet',
            'copies' => 1,
            'first_unlocked_at' => now(),
        ]);
        UserCollectible::create([
            'user_id' => $user->id,
            'collectible_key' => 'maquina_libro',
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
            'acquired_at' => now(),
        ]);

        $this->actingAs($user)->get('/album')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Album/Index')
                ->where('items', fn ($items) => collect($items)->contains(fn ($item) => $item['key'] === 'violet' && $item['unlocked'] === true))
                ->where('items', fn ($items) => collect($items)->contains(fn ($item) => $item['key'] === 'maquina_libro' && $item['unlocked'] === true))
                ->where('progress.unlocked', 2)
            );
    }
}
