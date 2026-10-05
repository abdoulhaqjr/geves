<?php

namespace Database\Factories;

use App\Models\EventSeries;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventSeries>
 */
class EventSeriesFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Colloque '.ucfirst(fake()->unique()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
        ];
    }
}
