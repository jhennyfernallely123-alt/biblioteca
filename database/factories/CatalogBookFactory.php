<?php

namespace Database\Factories;

use App\Models\CatalogBook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogBook>
 */
class CatalogBookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title' => ucfirst(fake()->sentence(3)),
            'author' => fake()->name(),
            'isbn' => null,
            'publisher' => fake()->company(),
            'published_year' => fake()->numberBetween(1900, 2026),
            'cover_path' => null,
        ];
    }
}
