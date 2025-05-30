<?php

namespace Database\Factories;

use App\Domain\Shared\Models\Coverage;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\Coverage>
 */
class CoverageFactory extends Factory
{
    protected $model = Coverage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->unique()->randomElement([
                'Hardware Only', 'Software Only', 'Hardware + Software', 'Full Coverage',
                'Parts Only', 'Labor Only', 'Parts + Labor', 'On-Site Support',
                'Remote Support', 'Depot Repair', 'Advanced Exchange', 'Keep Your Drive',
            ]),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => $this->faker->paragraph(),
            'coverage_type' => $this->faker->randomElement(['warranty', 'service_contract', 'maintenance']),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the coverage is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create full coverage.
     */
    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Full Coverage',
            'code' => 'FUL001',
            'coverage_type' => 'warranty',
        ]);
    }
}
