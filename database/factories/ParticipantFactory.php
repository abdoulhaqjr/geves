<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'institution' => 'Université de Kara',
            'country' => 'Togo',
            'title' => fake()->randomElement(['Doctorant', 'Docteur', 'Professeur']),
            'role' => 'author',
            'access_token' => Str::random(64),
        ];
    }

    public function reviewer(): static
    {
        return $this->state(fn () => ['role' => 'reviewer']);
    }

    public function coauthor(): static
    {
        return $this->state(fn () => ['role' => 'coauthor']);
    }
}
