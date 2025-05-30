<?php

namespace Database\Factories;

use App\Domain\Shared\Models\Status;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\Status>
 */
class StatusFactory extends Factory
{
    protected $model = Status::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->randomElement([
                'Active', 'Inactive', 'Pending', 'Suspended', 'Archived',
                'Draft', 'Published', 'Under Review', 'Approved', 'Rejected',
            ]) . ' ' . $this->faker->numberBetween(1, 999),
            'code' => $this->faker->regexify('[A-Z]{3}_[A-Z]{3}'),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['customer', 'asset', 'general']),
            'color' => $this->faker->hexColor(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the status is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an active status.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Active',
            'code' => 'ACT_STA',
            'color' => '#28a745',
        ]);
    }
}
