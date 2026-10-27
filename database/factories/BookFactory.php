<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shelf_id' => Shelf::factory(),
            'title' => ucfirst(fake()->sentence(3)),
            'author' => fake()->name(),
            'isbn' => fake()->isbn13(),
            'publisher' => fake()->company(),
            'published_year' => fake()->numberBetween(1900, 2026),
            'cover_path' => null,
            'rating' => fake()->numberBetween(0, 5),
            'notes' => null,
        ];
    }
}
