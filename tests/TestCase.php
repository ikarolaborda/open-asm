<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Domain\Organization\Models\Organization;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate');

        // Run roles and permissions seeder
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * Create and authenticate a user for testing.
     */
    protected function authenticateUser(array $attributes = []): User
    {
        $organization = Organization::factory()->create();

        $user = User::factory()->create(array_merge([
            'organization_id' => $organization->id,
        ], $attributes));

        // Assign admin role to test users by default
        $user->assignRole('admin');

        return $user;
    }

    /**
     * Create a user without authenticating.
     */
    protected function createUser(array $attributes = []): User
    {
        $organization = Organization::factory()->create();

        return User::factory()->create(array_merge([
            'organization_id' => $organization->id,
        ], $attributes));
    }

    /**
     * Get authentication headers for API testing using JWT.
     */
    protected function getAuthHeaders(?User $user = null): array
    {
        if (! $user) {
            $user = $this->authenticateUser();
        }

        $token = JWTAuth::fromUser($user);

        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Assert JSON response structure.
     */
    protected function assertJsonResponseStructure(array $structure, $response): void
    {
        $response->assertJsonStructure($structure);
    }

    /**
     * Assert API response format.
     */
    protected function assertApiResponse($response, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertHeader('content-type', 'application/json');
    }
}
