<?php

namespace Database\Factories;

use App\Domain\Customer\Models\Customer;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Customer\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->company(),
            'code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{4}'),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'industry' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Manufacturing', 'Retail']),
            'description' => $this->faker->optional()->paragraph(),
            'billing_address' => $this->faker->streetAddress(),
            'billing_city' => $this->faker->city(),
            'billing_state' => $this->faker->stateAbbr(),
            'billing_country' => $this->faker->countryCode(),
            'billing_postal_code' => $this->faker->postcode(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an enterprise tier customer.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => 'Enterprise Technology',
            'description' => 'Large enterprise customer with extensive technology needs.',
        ]);
    }

    /**
     * Create an SMB (Small-Medium Business) tier customer.
     */
    public function smb(): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => 'Small Business',
            'description' => 'Small to medium business customer.',
        ]);
    }
}
