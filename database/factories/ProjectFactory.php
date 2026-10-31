<?php

namespace Database\Factories;

use App\Enums\ProjectStage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
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
            'title' => fake()->company().' website',
            'stage' => ProjectStage::Building,
            'preview_url' => 'https://preview.example.com',
            'live_url' => null,
            'due_on' => now()->addWeeks(2),
        ];
    }
}
