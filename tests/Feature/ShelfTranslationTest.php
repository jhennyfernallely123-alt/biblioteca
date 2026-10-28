<?php

use App\Models\Shelf;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('traduce el nombre de la estantería sin cambiar el original', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $shelf = Shelf::factory()->for($user)->create(['name' => 'Pendientes']);

    Http::fake([
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'Pending'],
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('shelves.0.display_name', 'Pending'));

    expect($shelf->fresh()->name)->toBe('Pendientes');
});
