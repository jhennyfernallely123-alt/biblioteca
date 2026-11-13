<?php

namespace Database\Factories;

use App\Models\LiteraryUniverse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LiteraryUniverseFactory extends Factory
{
    protected $model = LiteraryUniverse::class;

    public function definition(): array
    {
        $types = array_keys(LiteraryUniverse::TYPES);

        return [
            'user_id' => fn () => User::factory()->create()->id,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'type' => $this->faker->randomElement($types),
            'total_books' => 0,
        ];
    }
}
