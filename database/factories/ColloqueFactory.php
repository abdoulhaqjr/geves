<?php

namespace Database\Factories;

use App\Models\Colloque;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Colloque>
 */
class ColloqueFactory extends Factory
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
