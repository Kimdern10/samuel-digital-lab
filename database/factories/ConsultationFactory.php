<?php

namespace Database\Factories;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '0803'.fake()->numerify('#######'),
            'email' => fake()->safeEmail(),
            'method' => 'whatsapp',
            'topic' => fake()->sentence(),
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'minutes' => 15,
            'status' => 'booked',
            'ip_address' => fake()->ipv4(),
        ];
    }
}
