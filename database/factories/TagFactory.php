<?php

namespace Database\Factories;

use App\Domain\Shared\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => \App\Domain\Organization\Models\Organization::factory(),
            'name' => $this->faker->randomElement([
                'Critical', 'Production', 'Development', 'Testing', 'Staging',
                'Legacy', 'New', 'Deprecated', 'High Priority', 'Low Priority',
                'Secure', 'Public', 'Internal', 'Backup', 'Primary',
            ]) . ' ' . $this->faker->numberBetween(1, 999),
            'code' => $this->faker->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the tag is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a critical tag.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Critical',
            'code' => 'CRT001',
            'color' => '#dc3545',
            'description' => 'Critical system component',
        ]);
    }
}
