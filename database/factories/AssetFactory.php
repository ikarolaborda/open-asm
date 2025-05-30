<?php

namespace Database\Factories;

use App\Domain\Asset\Models\Asset;
use App\Domain\Organization\Models\Organization;
use App\Domain\Customer\Models\Customer;
use App\Domain\Location\Models\Location;
use App\Domain\Shared\Models\Oem;
use App\Domain\Shared\Models\Product;
use App\Domain\Shared\Models\Type;
use App\Domain\Shared\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Asset\Models\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchaseDate = $this->faker->dateTimeBetween('-5 years', '-1 year');
        $installationDate = $this->faker->dateTimeBetween($purchaseDate, 'now');
        $warrantyStart = $this->faker->dateTimeBetween($purchaseDate, $installationDate);
        $warrantyEnd = $this->faker->dateTimeBetween($warrantyStart, '+3 years');

        return [
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'location_id' => Location::factory(),
            'oem_id' => Oem::factory(),
            'product_id' => Product::factory(),
            'type_id' => Type::factory(),
            'status_id' => Status::factory(),
            'serial_number' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{8}'),
            'asset_tag' => $this->faker->unique()->regexify('AST[0-9]{6}'),
            'model_number' => $this->faker->regexify('[A-Z]{2}[0-9]{3}[A-Z]'),
            'part_number' => $this->faker->regexify('[0-9]{6}-[A-Z]{3}'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'purchase_date' => $purchaseDate,
            'installation_date' => $installationDate,
            'warranty_start_date' => $warrantyStart,
            'warranty_end_date' => $warrantyEnd,
            'purchase_price' => $this->faker->randomFloat(2, 500, 50000),
            'current_value' => $this->faker->randomFloat(2, 100, 30000),
            'is_active' => true,
            'data_quality_score' => $this->faker->numberBetween(60, 100),
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the asset is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an asset with expired warranty.
     */
    public function expiredWarranty(): static
    {
        return $this->state(fn (array $attributes) => [
            'warranty_end_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }

    /**
     * Create an asset with warranty expiring soon.
     */
    public function warrantyExpiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'warranty_end_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }
}
