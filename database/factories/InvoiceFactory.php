<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => null,
            'title' => 'Website deposit (50%)',
            'items' => [['description' => 'Business website: 50% deposit', 'amount' => 7500000]],
            'amount' => 7500000,
            'status' => InvoiceStatus::Unpaid,
            'due_on' => now()->addWeek(),
            'notes' => null,
        ];
    }

    /**
     * Indicate that the invoice has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
            'payment_method' => 'bank_transfer',
        ]);
    }
}
