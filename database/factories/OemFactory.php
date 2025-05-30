<?php

namespace Database\Factories;

use App\Domain\Shared\Models\Oem;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\Oem>
 */
class OemFactory extends Factory
{
    protected $model = Oem::class;

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
                'Dell Technologies', 'HP Inc.', 'Lenovo', 'IBM', 'Cisco Systems',
                'Microsoft', 'Apple', 'Intel', 'AMD', 'NVIDIA', 'VMware',
                'Oracle', 'SAP', 'Salesforce', 'Adobe', 'Autodesk',
            ]) . ' ' . $this->faker->numberBetween(1, 999),
            'code' => $this->faker->regexify('[A-Z]{3}[0-9]{3}'),
            'website' => $this->faker->url(),
            'is_active' => true,
            'description' => $this->faker->optional()->paragraph(),
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the OEM is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a Dell OEM.
     */
    public function dell(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Dell Technologies',
            'code' => 'DEL001',
            'website' => 'https://www.dell.com',
        ]);
    }

    /**
     * Create an HP OEM.
     */
    public function hp(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'HP Inc.',
            'code' => 'HP001',
            'website' => 'https://www.hp.com',
        ]);
    }
}
