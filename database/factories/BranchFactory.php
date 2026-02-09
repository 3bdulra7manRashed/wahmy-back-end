<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Branch>
     */
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => fake()->company(),
                'ar' => 'فرع ' . fake()->word(),
            ],
            'address' => [
                'en' => fake()->address(),
                'ar' => fake()->address(),
            ],
            'description' => [
                'en' => fake()->sentence(),
                'ar' => fake()->sentence(),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
