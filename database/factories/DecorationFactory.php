<?php

namespace Database\Factories;

use App\Models\Decoration;
use App\Models\Shelf;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Decoration>
 */
class DecorationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shelf_id' => Shelf::factory(),
            'kind' => fake()->randomElement(Decoration::KINDS),
            'color' => fake()->randomElement(Decoration::COLORS),
            'after_book_id' => null,
            'sort' => 0,
        ];
    }
}
