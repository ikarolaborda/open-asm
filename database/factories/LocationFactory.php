<?php

namespace Database\Factories;

use App\Domain\Location\Models\Location;
use App\Domain\Organization\Models\Organization;
use App\Domain\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Location\Models\Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'name' => $this->faker->company() . ' ' . $this->faker->randomElement(['Office', 'Warehouse', 'Data Center', 'Branch']),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'country' => $this->faker->countryCode(),
            'postal_code' => $this->faker->postcode(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a data center location.
     */
    public function dataCenter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->company() . ' Data Center',
        ]);
    }
}
