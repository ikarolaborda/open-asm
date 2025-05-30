<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Asset\Models\Asset;
use App\Domain\Organization\Models\Organization;
use App\Domain\Customer\Models\Customer;
use App\Domain\Shared\Models\Type;
use App\Domain\Shared\Models\Tag;
use App\Models\User;

class AssetApiTest extends TestCase
{
    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->user = $this->authenticateUser(['organization_id' => $this->organization->id]);
    }

    public function test_can_list_assets()
    {
        Asset::factory()->count(3)->create(['organization_id' => $this->organization->id]);

        $response = $this->getJson('/api/v1/assets', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'serial_number',
                    'asset_tag',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_can_list_assets_with_pagination()
    {
        Asset::factory()->count(20)->create(['organization_id' => $this->organization->id]);

        $response = $this->getJson('/api/v1/assets?per_page=5', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $perPage = $response->json('meta.per_page');
        if (is_array($perPage)) {
            $perPage = $perPage[0];
        }
        $this->assertEquals(5, $perPage);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_can_filter_assets_by_active_status()
    {
        Asset::factory()->create(['organization_id' => $this->organization->id, 'is_active' => true]);
        Asset::factory()->create(['organization_id' => $this->organization->id, 'is_active' => false]);

        $response = $this->getJson('/api/v1/assets?filter[is_active]=1', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.is_active'));
    }

    public function test_can_filter_assets_by_customer()
    {
        $customer = Customer::factory()->create(['organization_id' => $this->organization->id]);
        Asset::factory()->create(['organization_id' => $this->organization->id, 'customer_id' => $customer->id]);
        Asset::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->getJson("/api/v1/assets?filter[customer_id]={$customer->id}", $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($customer->id, $response->json('data.0.customer_id'));
    }

    public function test_can_sort_assets()
    {
        Asset::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Z Asset']);
        Asset::factory()->create(['organization_id' => $this->organization->id, 'name' => 'A Asset']);

        $response = $this->getJson('/api/v1/assets?sort=name', $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $this->assertEquals('A Asset', $response->json('data.0.name'));
        $this->assertEquals('Z Asset', $response->json('data.1.name'));
    }

    public function test_can_create_asset()
    {
        $customer = Customer::factory()->create(['organization_id' => $this->organization->id]);
        $type = Type::factory()->create();

        $assetData = [
            'customer_id' => $customer->id,
            'type_id' => $type->id,
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
            'asset_tag' => 'AST001',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/assets', $assetData, $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'serial_number',
                'asset_tag',
                'is_active',
            ],
        ]);

        $this->assertDatabaseHas('assets', [
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_cannot_create_asset_without_required_fields()
    {
        $response = $this->postJson('/api/v1/assets', [], $this->getAuthHeaders($this->user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'serial_number', 'customer_id']);
    }

    public function test_cannot_create_asset_with_duplicate_serial_number()
    {
        $existingAsset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
            'serial_number' => 'SN123456789',
        ]);

        $customer = Customer::factory()->create(['organization_id' => $this->organization->id]);
        $type = Type::factory()->create();

        $assetData = [
            'customer_id' => $customer->id,
            'type_id' => $type->id,
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
        ];

        $response = $this->postJson('/api/v1/assets', $assetData, $this->getAuthHeaders($this->user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['serial_number']);
    }

    public function test_can_show_asset()
    {
        $asset = Asset::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->getJson("/api/v1/assets/{$asset->id}", $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'serial_number',
                'asset_tag',
                'is_active',
                'created_at',
                'updated_at',
            ],
        ]);
        $response->assertJsonPath('data.id', $asset->id);
    }

    public function test_can_show_asset_with_relationships()
    {
        $asset = Asset::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->getJson("/api/v1/assets/{$asset->id}?include=customer,type,warranties", $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'customer',
                'type',
                'warranties',
            ],
        ]);
    }

    public function test_cannot_show_asset_from_different_organization()
    {
        $otherOrganization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $otherOrganization->id]);

        $response = $this->getJson("/api/v1/assets/{$asset->id}", $this->getAuthHeaders($this->user));

        $response->assertStatus(404);
    }

    public function test_can_update_asset()
    {
        $asset = Asset::factory()->create(['organization_id' => $this->organization->id]);

        $updateData = [
            'name' => 'Updated Asset Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/v1/assets/{$asset->id}", $updateData, $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'description',
            ],
        ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Updated Asset Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_cannot_update_asset_from_different_organization()
    {
        $otherOrganization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $otherOrganization->id]);

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/v1/assets/{$asset->id}", $updateData, $this->getAuthHeaders($this->user));

        $response->assertStatus(404);
    }

    public function test_can_delete_asset()
    {
        $asset = Asset::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->deleteJson("/api/v1/assets/{$asset->id}", [], $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJson(['message' => 'Asset deleted successfully.']);

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    public function test_cannot_delete_asset_from_different_organization()
    {
        $otherOrganization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $otherOrganization->id]);

        $response = $this->deleteJson("/api/v1/assets/{$asset->id}", [], $this->getAuthHeaders($this->user));

        $response->assertStatus(404);
    }

    public function test_can_retire_asset()
    {
        $asset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/v1/assets/{$asset->id}/retire", [], $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJson(['message' => 'Asset retired successfully.']);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'is_active' => false,
        ]);
    }

    public function test_can_reactivate_asset()
    {
        $asset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
        ]);

        $response = $this->patchJson("/api/v1/assets/{$asset->id}/reactivate", [], $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJson(['message' => 'Asset reactivated successfully.']);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'is_active' => true,
        ]);
    }

    public function test_can_attach_tags_to_asset()
    {
        $asset = Asset::factory()->create(['organization_id' => $this->organization->id]);
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $response = $this->postJson("/api/v1/assets/{$asset->id}/tags", [
            'tag_ids' => [$tag1->id, $tag2->id],
        ], $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJson(['message' => 'Tags attached successfully.']);

        $this->assertDatabaseHas('asset_tags', [
            'asset_id' => $asset->id,
            'tag_id' => $tag1->id,
        ]);
        $this->assertDatabaseHas('asset_tags', [
            'asset_id' => $asset->id,
            'tag_id' => $tag2->id,
        ]);
    }

    public function test_can_detach_tags_from_asset()
    {
        $asset = Asset::factory()->create(['organization_id' => $this->organization->id]);
        $tag = Tag::factory()->create();
        $asset->tags()->attach($tag->id);

        $response = $this->deleteJson("/api/v1/assets/{$asset->id}/tags", [
            'tag_ids' => [$tag->id],
        ], $this->getAuthHeaders($this->user));

        $this->assertApiResponse($response, 200);
        $response->assertJson(['message' => 'Tags detached successfully.']);

        $this->assertDatabaseMissing('asset_tags', [
            'asset_id' => $asset->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_requires_authentication_for_all_endpoints()
    {
        $asset = Asset::factory()->create();

        // Test all endpoints without authentication
        $this->getJson('/api/v1/assets')->assertStatus(401);
        $this->postJson('/api/v1/assets', [])->assertStatus(401);
        $this->getJson("/api/v1/assets/{$asset->id}")->assertStatus(401);
        $this->putJson("/api/v1/assets/{$asset->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/assets/{$asset->id}")->assertStatus(401);
    }

    public function test_validates_json_content_type()
    {
        $response = $this->post('/api/v1/assets', [], $this->getAuthHeaders($this->user));

        $response->assertStatus(422);
    }

    public function test_returns_json_responses()
    {
        $response = $this->getJson('/api/v1/assets', $this->getAuthHeaders($this->user));

        $response->assertHeader('content-type', 'application/json');
    }
}
