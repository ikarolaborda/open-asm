<?php

namespace Database\Factories;

use App\Domain\Asset\Models\AssetWarranty;
use App\Domain\Asset\Models\Asset;
use App\Domain\Shared\Models\ServiceLevel;
use App\Domain\Shared\Models\Coverage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Asset\Models\AssetWarranty>
 */
class AssetWarrantyFactory extends Factory
{
    protected $model = AssetWarranty::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+3 years');

        return [
            'asset_id' => Asset::factory(),
            'service_level_id' => ServiceLevel::factory(),
            'coverage_id' => Coverage::factory(),
            'warranty_type' => $this->faker->randomElement(['Standard', 'Extended', 'Premium', 'Basic']),
            'contract_number' => $this->faker->unique()->regexify('WTY[0-9]{8}'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $this->faker->paragraph(),
            'cost' => $this->faker->randomFloat(2, 100, 5000),
            'provider' => $this->faker->company(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the warranty is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an expired warranty.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }
}
