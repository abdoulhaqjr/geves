<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();
        $email = fake()->unique()->safeEmail();

        return [
            'edition_id' => Edition::factory(),
            'participant_id' => Participant::factory(),
            'ref_code' => strtoupper(fake()->unique()->bothify('??-###')),
            'access_token' => Str::random(64),
            'title' => fake()->sentence(),
            'authors' => [['name' => $name, 'email' => $email, 'corresponding' => true]],
            'abstract' => fake()->paragraph(),
            'keywords' => implode(', ', fake()->words(3)),
            'track' => 'Sciences',
            'type' => 'oral',
            'status' => 'submitted',
            'revision_of' => 0,
            'version' => 1,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => 'accepted']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
