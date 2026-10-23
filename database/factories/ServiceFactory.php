<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->company().' website',
            'description' => fake()->sentence(12),
            'features' => fake()->sentences(3),
            'price' => fake()->numberBetween(5, 40) * 10000,
            'price_note' => 'from · 7 days',
            'badge' => null,
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the service is hidden from the home page.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
