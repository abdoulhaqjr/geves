<?php

namespace Database\Factories;

use App\Models\Account;
use App\Support\Civilities;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'civility' => fake()->randomElement(Civilities::all()),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'grade' => fake()->randomElement(['Doctorant', 'Docteur', 'Professeur']),
            'affiliation' => 'Université de Kara',
            'country' => 'Togo',
            'email_verified_at' => now(),
        ];
    }

    /** E-mail non vérifié (ne peut pas soumettre). */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /** Profil incomplet (affiliation manquante). */
    public function incomplete(): static
    {
        return $this->state(fn () => ['affiliation' => null]);
    }
}
