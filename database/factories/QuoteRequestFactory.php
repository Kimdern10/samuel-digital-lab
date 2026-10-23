<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteRequest>
 */
class QuoteRequestFactory extends Factory
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
            'business' => fake()->company(),
            'phone' => '0803'.fake()->numerify('#######'),
            'email' => fake()->safeEmail(),
            'service' => 'Business & portfolio websites',
            'budget' => QuoteRequest::BUDGETS[1],
            'details' => fake()->paragraph(),
            'status' => QuoteStatus::New,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
