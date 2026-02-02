<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Idea>
 */
class IdeaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'category_id' => \App\Models\IdeaCategory::factory(),
            'problem_statement' => $this->faker->paragraph(),
            'proposed_solution' => $this->faker->paragraph(),
            'cost_benefit_analysis' => $this->faker->paragraph(),
            'collaboration_enabled' => $this->faker->boolean(),
            'status' => 'draft',
            'current_review_cycle' => $this->faker->numberBetween(1, 5),
        ];
    }
}
