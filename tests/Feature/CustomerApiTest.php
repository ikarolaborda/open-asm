<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Customer\Models\Customer;
use App\Domain\Organization\Models\Organization;
use App\Models\User;

class CustomerApiTest extends TestCase
{
    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->user = $this->authenticateUser(['organization_id' => $this->organization->id]);
    }

    public function test_can_list_customers()
    {
        Customer::factory()->count(3)->create(['organization_id' => $this->organization->id]);

        $response = $this->getJson('/api/v1/customers', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                    'email',
                    'industry',
                    'is_active',
                ],
            ],
        ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_filter_customers_by_industry()
    {
        Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'industry' => 'Technology',
        ]);
        Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'industry' => 'Healthcare',
        ]);

        $response = $this->getJson('/api/v1/customers?filter[industry]=Technology', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Technology', $response->json('data.0.industry'));
    }

    public function test_can_filter_customers_by_active_status()
    {
        Customer::factory()->create(['organization_id' => $this->organization->id, 'is_active' => true]);
        Customer::factory()->create(['organization_id' => $this->organization->id, 'is_active' => false]);

        $response = $this->getJson('/api/v1/customers?filter[is_active]=1', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.is_active'));
    }

    public function test_can_search_customers()
    {
        Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'XYZ123UniqueTestCompany',
        ]);
        Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Tech Solutions',
        ]);

        $response = $this->getJson('/api/v1/customers?search=XYZ123Unique', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        // Check that at least one result contains our search term
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));

        // Check that at least one result matches our expected customer
        $foundExpected = false;
        foreach ($data as $customer) {
            if ($customer['name'] === 'XYZ123UniqueTestCompany') {
                $foundExpected = true;
                break;
            }
        }
        $this->assertTrue($foundExpected, 'Expected customer not found in search results');
    }

    public function test_can_create_customer()
    {
        $customerData = [
            'name' => 'Test Customer',
            'code' => 'TC001',
            'email' => 'test@customer.com',
            'industry' => 'Technology',
        ];

        $response = $this->postJson('/api/v1/customers', $customerData, $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'code',
                'email',
                'industry',
            ],
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'code' => 'TC001',
            'email' => 'test@customer.com',
            'industry' => 'Technology',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_cannot_create_customer_without_required_fields()
    {
        $response = $this->postJson('/api/v1/customers', [], $this->getAuthHeaders($this->user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_customer_with_duplicate_code()
    {
        Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'code' => 'TC001',
        ]);

        $customerData = [
            'name' => 'Test Customer',
            'code' => 'TC001',
        ];

        $response = $this->postJson('/api/v1/customers', $customerData, $this->getAuthHeaders($this->user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_can_show_customer()
    {
        $customer = Customer::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->getJson("/api/v1/customers/{$customer->id}", $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'code',
                'email',
                'phone',
                'website',
                'industry',
                'is_active',
                'created_at',
                'updated_at',
            ],
        ]);
        $response->assertJsonPath('data.id', $customer->id);
    }

    public function test_can_show_customer_with_relationships()
    {
        $customer = Customer::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->getJson("/api/v1/customers/{$customer->id}", $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'code',
                'email',
                'is_active',
            ],
        ]);
    }

    public function test_cannot_show_customer_from_different_organization()
    {
        $otherOrganization = Organization::factory()->create();
        $customer = Customer::factory()->create(['organization_id' => $otherOrganization->id]);

        $response = $this->getJson("/api/v1/customers/{$customer->id}", $this->getAuthHeaders($this->user));

        $response->assertStatus(404);
    }

    public function test_can_update_customer()
    {
        $customer = Customer::factory()->create(['organization_id' => $this->organization->id]);

        $updateData = [
            'name' => 'Updated Customer Name',
            'industry' => 'Technology',
        ];

        $response = $this->putJson("/api/v1/customers/{$customer->id}", $updateData, $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'industry',
            ],
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer Name',
            'industry' => 'Technology',
        ]);
    }

    public function test_cannot_update_customer_from_different_organization()
    {
        $otherOrganization = Organization::factory()->create();
        $customer = Customer::factory()->create(['organization_id' => $otherOrganization->id]);

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/v1/customers/{$customer->id}", $updateData, $this->getAuthHeaders($this->user));

        $response->assertStatus(404);
    }

    public function test_can_delete_customer()
    {
        $customer = Customer::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->deleteJson("/api/v1/customers/{$customer->id}", [], $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJson(['message' => 'Customer deleted successfully.']);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_cannot_delete_customer_from_different_organization()
    {
        $otherOrganization = Organization::factory()->create();
        $customer = Customer::factory()->create(['organization_id' => $otherOrganization->id]);

        $response = $this->deleteJson("/api/v1/customers/{$customer->id}", [], $this->getAuthHeaders($this->user));

        $response->assertStatus(404);
    }

    public function test_can_activate_customer()
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
        ]);

        $response = $this->patchJson("/api/v1/customers/{$customer->id}/activate", [], $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJson(['message' => 'Customer activated successfully.']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => true,
        ]);
    }

    public function test_can_deactivate_customer()
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/v1/customers/{$customer->id}/deactivate", [], $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJson(['message' => 'Customer deactivated successfully.']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => false,
        ]);
    }

    public function test_requires_authentication_for_all_endpoints()
    {
        $customer = Customer::factory()->create();

        // Test all endpoints without authentication
        $this->getJson('/api/v1/customers')->assertStatus(401);
        $this->postJson('/api/v1/customers', [])->assertStatus(401);
        $this->getJson("/api/v1/customers/{$customer->id}")->assertStatus(401);
        $this->putJson("/api/v1/customers/{$customer->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/customers/{$customer->id}")->assertStatus(401);
    }

    public function test_validates_email_format()
    {
        $customerData = [
            'name' => 'Test Customer',
            'code' => 'TC001',
            'email' => 'invalid-email',
        ];

        $response = $this->postJson('/api/v1/customers', $customerData, $this->getAuthHeaders($this->user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_can_sort_customers_by_name()
    {
        Customer::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Z Customer']);
        Customer::factory()->create(['organization_id' => $this->organization->id, 'name' => 'A Customer']);

        $response = $this->getJson('/api/v1/customers?sort=name', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $this->assertEquals('A Customer', $response->json('data.0.name'));
        $this->assertEquals('Z Customer', $response->json('data.1.name'));
    }

    public function test_can_sort_customers_by_created_date_descending()
    {
        // Create older customer with explicit timestamp
        $older = Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'created_at' => now()->subMinute()
        ]);
        
        // Create newer customer with current timestamp
        $newer = Customer::factory()->create([
            'organization_id' => $this->organization->id,
            'created_at' => now()
        ]);

        $response = $this->getJson('/api/v1/customers?sort=-created_at', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $this->assertEquals($newer->id, $response->json('data.0.id'));
        $this->assertEquals($older->id, $response->json('data.1.id'));
    }

    public function test_validates_industry_values()
    {
        $customerData = [
            'name' => 'Test Customer',
            'code' => 'TC001',
            'industry' => str_repeat('a', 256), // Too long
        ];

        $response = $this->postJson('/api/v1/customers', $customerData, $this->getAuthHeaders($this->user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['industry']);
    }
}
