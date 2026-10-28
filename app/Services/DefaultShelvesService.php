<?php

namespace App\Services;

use App\Models\User;

class DefaultShelvesService
{
    public function createFor(User $user): void
    {
        foreach (['Pendientes', 'Leyendo', 'Leídos'] as $position => $name) {
            $user->shelves()->create([
                'name' => $name,
                'position' => $position,
            ]);
        }
    }
}
