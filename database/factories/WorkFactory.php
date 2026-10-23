<?php

namespace Database\Factories;

use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Work>
 */
class WorkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->company().' website';

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'client' => fake()->company(),
            'category' => fake()->randomElement(['Business website', 'E-commerce website', 'Blog & news website', 'Landing page']),
            'summary' => fake()->sentence(14),
            'headline' => fake()->sentence(8),
            'description' => fake()->paragraphs(2, true),
            'highlights' => fake()->sentences(3),
            'results' => [
                ['value' => '59 → 92', 'label' => 'mobile speed score'],
            ],
            'technologies' => ['Laravel', 'MySQL', 'Tailwind CSS'],
            'live_url' => fake()->url(),
            'cover_path' => null,
            'screenshots' => [],
            'seo_description' => null,
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the work is a draft hidden from visitors.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    /**
     * Indicate that the work is shown on the home page.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
