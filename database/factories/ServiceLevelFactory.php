<?php

namespace Database\Factories;

use App\Domain\Shared\Models\ServiceLevel;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\ServiceLevel>
 */
class ServiceLevelFactory extends Factory
{
    protected $model = ServiceLevel::class;

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
                'Basic Support', 'Standard Support', 'Premium Support', 'Enterprise Support',
                '24x7 Support', 'Business Hours', 'Next Business Day', 'Same Day',
                '4 Hour Response', '2 Hour Response', '1 Hour Response',
            ]),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => $this->faker->paragraph(),
            'response_time_hours' => $this->faker->randomElement([1, 2, 4, 8, 24, 48, 72]),
            'resolution_time_hours' => $this->faker->randomElement([4, 8, 24, 48, 72, 168]),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the service level is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a premium service level.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium Support',
            'code' => 'PRM001',
            'response_time_hours' => 1,
            'resolution_time_hours' => 4,
        ]);
    }
}
