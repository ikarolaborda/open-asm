<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\AssetWarranty;
use App\Domain\Organization\Models\Organization;
use App\Domain\Customer\Models\Customer;
use App\Domain\Location\Models\Location;
use App\Domain\Shared\Models\Oem;
use App\Domain\Shared\Models\Product;
use App\Domain\Shared\Models\Type;
use App\Domain\Shared\Models\Status;
use App\Domain\Shared\Models\Tag;

class AssetTest extends TestCase
{
    public function test_asset_can_be_created_with_required_fields()
    {
        $organization = Organization::factory()->create();
        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $type = Type::factory()->create();

        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'type_id' => $type->id,
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
        ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
        ]);
    }

    public function test_asset_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $asset->organization);
        $this->assertEquals($organization->id, $asset->organization->id);
    }

    public function test_asset_belongs_to_customer()
    {
        $customer = Customer::factory()->create();
        $asset = Asset::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $asset->customer);
        $this->assertEquals($customer->id, $asset->customer->id);
    }

    public function test_asset_belongs_to_location()
    {
        $location = Location::factory()->create();
        $asset = Asset::factory()->create(['location_id' => $location->id]);

        $this->assertInstanceOf(Location::class, $asset->location);
        $this->assertEquals($location->id, $asset->location->id);
    }

    public function test_asset_belongs_to_oem()
    {
        $oem = Oem::factory()->create();
        $asset = Asset::factory()->create(['oem_id' => $oem->id]);

        $this->assertInstanceOf(Oem::class, $asset->oem);
        $this->assertEquals($oem->id, $asset->oem->id);
    }

    public function test_asset_belongs_to_product()
    {
        $product = Product::factory()->create();
        $asset = Asset::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $asset->product);
        $this->assertEquals($product->id, $asset->product->id);
    }

    public function test_asset_belongs_to_type()
    {
        $type = Type::factory()->create();
        $asset = Asset::factory()->create(['type_id' => $type->id]);

        $this->assertInstanceOf(Type::class, $asset->type);
        $this->assertEquals($type->id, $asset->type->id);
    }

    public function test_asset_belongs_to_status()
    {
        $status = Status::factory()->create();
        $asset = Asset::factory()->create(['status_id' => $status->id]);

        $this->assertInstanceOf(Status::class, $asset->status);
        $this->assertEquals($status->id, $asset->status->id);
    }

    public function test_asset_has_many_warranties()
    {
        $asset = Asset::factory()->create();
        $warranty1 = AssetWarranty::factory()->create(['asset_id' => $asset->id]);
        $warranty2 = AssetWarranty::factory()->create(['asset_id' => $asset->id]);

        $this->assertCount(2, $asset->warranties);
        $this->assertTrue($asset->warranties->contains($warranty1));
        $this->assertTrue($asset->warranties->contains($warranty2));
    }

    public function test_asset_belongs_to_many_tags()
    {
        $asset = Asset::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $asset->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $asset->tags);
        $this->assertTrue($asset->tags->contains($tag1));
        $this->assertTrue($asset->tags->contains($tag2));
    }

    public function test_calculate_data_quality_score_with_all_required_fields()
    {
        $asset = new Asset([
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
            'customer_id' => Customer::factory()->create()->id,
            'type_id' => Type::factory()->create()->id,
        ]);

        $score = $asset->calculateDataQualityScore();
        $this->assertEquals(70, $score); // Only required fields = 70%
    }

    public function test_calculate_data_quality_score_with_all_fields()
    {
        $asset = Asset::factory()->make([
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
            'customer_id' => Customer::factory()->create()->id,
            'type_id' => Type::factory()->create()->id,
            'asset_tag' => 'AST123456',
            'model_number' => 'MODEL123',
            'part_number' => 'PART123',
            'description' => 'Test description',
            'purchase_date' => now(),
            'installation_date' => now(),
            'warranty_start_date' => now(),
            'warranty_end_date' => now()->addYear(),
            'purchase_price' => 1000.00,
            'location_id' => Location::factory()->create()->id,
            'oem_id' => Oem::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'status_id' => Status::factory()->create()->id,
        ]);

        $score = $asset->calculateDataQualityScore();
        $this->assertEquals(100, $score); // All fields = 100%
    }

    public function test_is_active_returns_true_for_active_asset()
    {
        $asset = Asset::factory()->create(['is_active' => true]);
        $this->assertTrue($asset->isActive());
    }

    public function test_is_active_returns_false_for_inactive_asset()
    {
        $asset = Asset::factory()->create(['is_active' => false]);
        $this->assertFalse($asset->isActive());
    }

    public function test_is_retired_returns_true_for_inactive_asset()
    {
        $asset = Asset::factory()->create(['is_active' => false]);
        $this->assertTrue($asset->isRetired());
    }

    public function test_is_retired_returns_false_for_active_asset()
    {
        $asset = Asset::factory()->create(['is_active' => true]);
        $this->assertFalse($asset->isRetired());
    }

    public function test_has_active_warranty_returns_true_when_warranty_exists()
    {
        $asset = Asset::factory()->create();
        AssetWarranty::factory()->create([
            'asset_id' => $asset->id,
            'is_active' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $this->assertTrue($asset->hasActiveWarranty());
    }

    public function test_has_active_warranty_returns_false_when_no_warranty()
    {
        $asset = Asset::factory()->create();
        $this->assertFalse($asset->hasActiveWarranty());
    }

    public function test_warranty_status_returns_no_warranty_when_none_exists()
    {
        $asset = Asset::factory()->create();
        $this->assertEquals('no_warranty', $asset->warranty_status);
    }

    public function test_warranty_status_returns_active_for_valid_warranty()
    {
        $asset = Asset::factory()->create();
        AssetWarranty::factory()->create([
            'asset_id' => $asset->id,
            'is_active' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
        ]);

        $this->assertEquals('active', $asset->warranty_status);
    }

    public function test_warranty_status_returns_expiring_soon_for_warranty_ending_within_30_days()
    {
        $asset = Asset::factory()->create();
        AssetWarranty::factory()->create([
            'asset_id' => $asset->id,
            'is_active' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(15),
        ]);

        $this->assertEquals('expiring_soon', $asset->warranty_status);
    }

    public function test_warranty_status_returns_expired_for_past_warranty()
    {
        $asset = Asset::factory()->create();
        AssetWarranty::factory()->create([
            'asset_id' => $asset->id,
            'is_active' => true,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);

        $this->assertEquals('expired', $asset->warranty_status);
    }

    public function test_retire_sets_asset_to_inactive()
    {
        $asset = Asset::factory()->create(['is_active' => true]);
        $asset->retire();

        $this->assertFalse($asset->fresh()->is_active);
    }

    public function test_reactivate_sets_asset_to_active()
    {
        $asset = Asset::factory()->create(['is_active' => false]);
        $asset->reactivate();

        $this->assertTrue($asset->fresh()->is_active);
    }

    public function test_scope_active_returns_only_active_assets()
    {
        Asset::factory()->create(['is_active' => true]);
        Asset::factory()->create(['is_active' => false]);

        $activeAssets = Asset::active()->get();
        $this->assertCount(1, $activeAssets);
        $this->assertTrue($activeAssets->first()->is_active);
    }

    public function test_scope_retired_returns_only_inactive_assets()
    {
        Asset::factory()->create(['is_active' => true]);
        Asset::factory()->create(['is_active' => false]);

        $retiredAssets = Asset::retired()->get();
        $this->assertCount(1, $retiredAssets);
        $this->assertFalse($retiredAssets->first()->is_active);
    }

    public function test_scope_search_finds_assets_by_name()
    {
        Asset::factory()->create(['name' => 'Test Server']);
        Asset::factory()->create(['name' => 'Production Laptop']);

        $results = Asset::search('Server')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Test Server', $results->first()->name);
    }

    public function test_scope_search_finds_assets_by_serial_number()
    {
        Asset::factory()->create(['serial_number' => 'SN123456789']);
        Asset::factory()->create(['serial_number' => 'SN987654321']);

        $results = Asset::search('123456')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('SN123456789', $results->first()->serial_number);
    }

    public function test_scope_for_customer_filters_by_customer()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        Asset::factory()->create(['customer_id' => $customer1->id]);
        Asset::factory()->create(['customer_id' => $customer2->id]);

        $results = Asset::forCustomer($customer1->id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($customer1->id, $results->first()->customer_id);
    }

    public function test_scope_at_location_filters_by_location()
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();

        Asset::factory()->create(['location_id' => $location1->id]);
        Asset::factory()->create(['location_id' => $location2->id]);

        $results = Asset::atLocation($location1->id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($location1->id, $results->first()->location_id);
    }

    public function test_data_quality_score_is_calculated_on_creation()
    {
        $asset = Asset::factory()->create([
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
        ]);

        $this->assertNotNull($asset->data_quality_score);
        $this->assertGreaterThan(0, $asset->data_quality_score);
    }

    public function test_data_quality_score_is_recalculated_on_update()
    {
        $asset = Asset::create([
            'organization_id' => \App\Domain\Organization\Models\Organization::factory()->create()->id,
            'customer_id' => \App\Domain\Customer\Models\Customer::factory()->create()->id,
            'type_id' => \App\Domain\Shared\Models\Type::factory()->create()->id,
            'name' => 'Test Asset',
            'serial_number' => 'SN123456789',
            'is_active' => true,
        ]);

        $originalScore = $asset->data_quality_score;

        $asset->update([
            'asset_tag' => 'AST123456',
            'model_number' => 'MODEL123',
        ]);

        $this->assertGreaterThan($originalScore, $asset->fresh()->data_quality_score);
    }
}
