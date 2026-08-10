<?php

namespace Database\Factories;

use App\Models\Colloque;
use App\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->numberBetween(2024, 2030);
        $title = "UK {$year}";

        return [
            'colloque_id' => Colloque::factory(),
            'title' => $title,
            'edition_label' => fake()->numberBetween(1, 12).'e édition',
            'year' => $year,
            'status' => 'open',
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'submission_open' => true,
            'settings' => ['tracks' => ['Sciences', 'Santé'], 'max_communications' => 3],
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'submission_open' => false]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => 'closed', 'submission_open' => false]);
    }
}
