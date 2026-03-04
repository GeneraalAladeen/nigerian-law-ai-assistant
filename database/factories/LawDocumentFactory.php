<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LawDocument>
 */
class LawDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(['act', 'regulation', 'decree', 'constitution', 'criminal', 'civil']),
            'jurisdiction' => fake()->randomElement(['federal', 'fct', 'state:lagos', 'state:kano', 'state:rivers']),
            'source' => fake()->sentence(),
            'year' => fake()->numberBetween(1960, 2024),
            'file_path' => 'laws/'.fake()->uuid().'.pdf',
            'status' => 'pending',
            'chunk_count' => 0,
            'processed_at' => null,
        ];
    }

    /**
     * Indicate the document has been successfully processed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'chunk_count' => fake()->numberBetween(5, 50),
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate the document is currently being processed.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Indicate the document failed to process.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
