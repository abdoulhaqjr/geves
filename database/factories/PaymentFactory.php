<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'participant_id' => Participant::factory(),
            'amount' => fake()->randomElement([10000, 15000, 20000]),
            'currency' => 'XOF',
            'method' => 'cash',
            'gateway' => 'manual',
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'paid_at' => now(),
            'receipt_no' => 'REC-'.now()->year.'-'.fake()->unique()->numberBetween(1, 9999),
        ]);
    }
}
