<?php

namespace Database\Factories;

use App\Domain\Shared\Models\ProductLine;
use App\Domain\Shared\Models\Oem;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\ProductLine>
 */
class ProductLineFactory extends Factory
{
    protected $model = ProductLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'oem_id' => Oem::factory(),
            'name' => $this->faker->randomElement([
                'PowerEdge', 'OptiPlex', 'Latitude', 'Precision', 'Inspiron',
                'EliteBook', 'ProBook', 'Pavilion', 'ThinkPad', 'IdeaPad',
                'MacBook', 'iMac', 'Mac Pro', 'Surface', 'Xbox',
            ]) . ' ' . $this->faker->numberBetween(1, 999),
            'code' => $this->faker->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the product line is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
