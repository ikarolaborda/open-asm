<?php

namespace Database\Factories;

use App\Domain\Shared\Models\Type;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\Type>
 */
class TypeFactory extends Factory
{
    protected $model = Type::class;

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
                'Server', 'Laptop', 'Desktop', 'Printer', 'Network Switch',
                'Router', 'Firewall', 'Storage', 'Monitor', 'Tablet',
                'Phone', 'Camera', 'Projector', 'Scanner',
            ]) . ' ' . $this->faker->numberBetween(1, 999),
            'code' => $this->faker->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a server type.
     */
    public function server(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Server',
            'code' => 'SRV001',
            'description' => 'Physical or virtual server hardware',
        ]);
    }
}
