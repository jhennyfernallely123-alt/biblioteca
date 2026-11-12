<?php

use App\Models\CardDraw;
use App\Models\User;

it('guarda el tipo y la clave del objeto de cada tirada', function () {
    $user = User::factory()->create();

    $draw = CardDraw::create([
        'user_id' => $user->id,
        'draw_date' => '2026-11-10',
        'kind' => 'sticker',
        'item_key' => 'maquina_libro',
        'ball_index' => 2,
        'duplicate' => false,
        'ink_earned' => 0,
    ]);

    expect($draw->fresh()->kind)->toBe('sticker')
        ->and($draw->fresh()->item_key)->toBe('maquina_libro');
});
