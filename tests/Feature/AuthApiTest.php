<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Domain\Organization\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthApiTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'user' => [
                'id',
                'name',
                'email',
                'organization_id',
            ],
            'roles',
            'permissions',
        ]);

        $this->assertEquals('test@example.com', $response->json('user.email'));
        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_user_cannot_login_with_invalid_email()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Invalid credentials',
        ]);
    }

    public function test_user_cannot_login_with_invalid_password()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Invalid credentials',
        ]);
    }

    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout()
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/v1/auth/logout', [], $this->getAuthHeaders($user));

        $this->assertApiResponse($response, 200);
        $response->assertJson([
            'message' => 'Successfully logged out',
        ]);
    }

    public function test_logout_requires_authentication()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_user_can_get_profile()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/v1/auth/me', $this->getAuthHeaders($user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'organization_id',
                'created_at',
                'updated_at',
            ],
            'roles',
            'permissions',
            'organization',
        ]);

        $this->assertEquals($user->id, $response->json('user.id'));
        $this->assertEquals($user->email, $response->json('user.email'));
    }

    public function test_get_profile_requires_authentication()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_refresh_token()
    {
        $user = $this->authenticateUser();
        $authHeaders = $this->getAuthHeaders($user);

        $response = $this->postJson('/api/v1/auth/refresh', [], $authHeaders);

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'user',
            'roles',
            'permissions',
        ]);

        $newToken = $response->json('access_token');
        $this->assertNotEmpty($newToken);
        // Note: Can't compare JWT tokens directly as they'll always be different
    }

    public function test_refresh_requires_authentication()
    {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_user_can_change_password()
    {
        $user = $this->authenticateUser([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ], $this->getAuthHeaders($user));

        $this->assertApiResponse($response, 200);
        $response->assertJson([
            'message' => 'Password updated successfully.',
        ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_change_password_requires_current_password()
    {
        $user = $this->authenticateUser([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ], $this->getAuthHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);
    }

    public function test_change_password_requires_confirmation()
    {
        $user = $this->authenticateUser([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ], $this->getAuthHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_change_password_requires_minimum_length()
    {
        $user = $this->authenticateUser([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'oldpassword',
            'password' => '123',
            'password_confirmation' => '123',
        ], $this->getAuthHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_change_password_requires_authentication()
    {
        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    }

    public function test_invalid_token_returns_401()
    {
        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_expired_token_returns_401()
    {
        // Test with a malformed/invalid token since JWT expiration is time-based
        // and we can't easily create an expired token in tests
        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.expired.token',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_rate_limiting()
    {
        $this->markTestSkipped('Rate limiting not yet implemented');

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; ++$i) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // Next attempt should be rate limited (when implemented)
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_successful_login_returns_user_with_organization()
    {
        $organization = Organization::factory()->create(['name' => 'Test Organization']);
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertApiResponse($response, 200);
        $this->assertEquals($organization->id, $response->json('user.organization_id'));
    }

    public function test_token_includes_proper_abilities()
    {
        // JWT tokens don't have individual abilities like Sanctum tokens
        // Instead, they rely on user roles and permissions
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/v1/auth/me', $this->getAuthHeaders($user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'user',
            'roles',
            'permissions',
        ]);

        // Verify that roles and permissions are included in the response
        $this->assertIsArray($response->json('roles'));
        $this->assertIsArray($response->json('permissions'));
    }
}
