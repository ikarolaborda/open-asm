<?php

namespace Database\Factories;

use App\Domain\Shared\Models\Product;
use App\Domain\Shared\Models\Oem;
use App\Domain\Shared\Models\ProductLine;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

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
            'product_line_id' => ProductLine::factory(),
            'name' => $this->faker->words(3, true),
            'model_number' => $this->faker->regexify('[A-Z]{2}[0-9]{3}[A-Z]'),
            'part_number' => $this->faker->regexify('[0-9]{6}-[A-Z]{3}'),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
