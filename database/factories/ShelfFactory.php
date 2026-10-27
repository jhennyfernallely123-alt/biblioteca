<?php

namespace Database\Factories;

use App\Models\Shelf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shelf>
 */
class ShelfFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => ucfirst(fake()->words(2, true)),
            'position' => 0,
            'view_mode' => 'covers',
        ];
    }
}
