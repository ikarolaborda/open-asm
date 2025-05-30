<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Customer\Models\Customer;
use App\Domain\Organization\Models\Organization;
use App\Domain\Shared\Models\Contact;
use App\Domain\Shared\Models\Status;
use App\Domain\Asset\Models\Asset;
use App\Domain\Location\Models\Location;

class CustomerTest extends TestCase
{
    public function test_customer_can_be_created_with_required_fields()
    {
        $organization = Organization::factory()->create();

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Test Customer',
            'code' => 'TC001',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Test Customer',
            'code' => 'TC001',
        ]);
    }

    public function test_customer_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $customer = Customer::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $customer->organization);
        $this->assertEquals($organization->id, $customer->organization->id);
    }

    public function test_customer_has_many_statuses_relationship()
    {
        $customer = Customer::factory()->create();
        $status = Status::factory()->create(['organization_id' => $customer->organization_id]);

        // Use the many-to-many relationship
        $customer->statuses()->attach($status->id, ['is_current' => true]);

        $this->assertCount(1, $customer->statuses);
        $this->assertTrue($customer->statuses->contains($status));
    }

    public function test_customer_has_many_contacts_relationship()
    {
        $customer = Customer::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $customer->organization_id]);

        // Use the many-to-many relationship
        $customer->contacts()->attach($contact->id, ['is_primary' => true]);

        $this->assertCount(1, $customer->contacts);
        $this->assertTrue($customer->contacts->contains($contact));
    }

    public function test_customer_has_many_assets()
    {
        $customer = Customer::factory()->create();
        $asset1 = Asset::factory()->create(['customer_id' => $customer->id]);
        $asset2 = Asset::factory()->create(['customer_id' => $customer->id]);

        $this->assertCount(2, $customer->assets);
        $this->assertTrue($customer->assets->contains($asset1));
        $this->assertTrue($customer->assets->contains($asset2));
    }

    public function test_customer_has_many_locations()
    {
        $customer = Customer::factory()->create();
        $location1 = Location::factory()->create(['customer_id' => $customer->id]);
        $location2 = Location::factory()->create(['customer_id' => $customer->id]);

        $this->assertCount(2, $customer->locations);
        $this->assertTrue($customer->locations->contains($location1));
        $this->assertTrue($customer->locations->contains($location2));
    }

    public function test_customer_scope_active_returns_only_active_customers()
    {
        Customer::factory()->create(['is_active' => true]);
        Customer::factory()->create(['is_active' => false]);

        $activeCustomers = Customer::active()->get();
        $this->assertCount(1, $activeCustomers);
        $this->assertTrue($activeCustomers->first()->is_active);
    }

    public function test_customer_scope_inactive_returns_only_inactive_customers()
    {
        Customer::factory()->create(['is_active' => true]);
        Customer::factory()->create(['is_active' => false]);

        $inactiveCustomers = Customer::inactive()->get();
        $this->assertCount(1, $inactiveCustomers);
        $this->assertFalse($inactiveCustomers->first()->is_active);
    }

    public function test_customer_scope_by_industry_filters_correctly()
    {
        Customer::factory()->enterprise()->create();
        Customer::factory()->smb()->create();
        Customer::factory()->create(['industry' => 'Startup']);

        $enterpriseCustomers = Customer::byIndustry('Enterprise Technology')->get();
        $this->assertCount(1, $enterpriseCustomers);
        $this->assertEquals('Enterprise Technology', $enterpriseCustomers->first()->industry);
    }

    public function test_customer_scope_search_finds_by_name()
    {
        Customer::factory()->create(['name' => 'Acme Corporation']);
        Customer::factory()->create(['name' => 'Tech Solutions Inc']);

        $results = Customer::search('Acme')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Acme Corporation', $results->first()->name);
    }

    public function test_customer_scope_search_finds_by_code()
    {
        Customer::factory()->create(['code' => 'ACME001']);
        Customer::factory()->create(['code' => 'TECH002']);

        $results = Customer::search('ACME')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('ACME001', $results->first()->code);
    }

    public function test_customer_scope_search_finds_by_email()
    {
        Customer::factory()->create(['email' => 'contact@acme.com']);
        Customer::factory()->create(['email' => 'info@tech.com']);

        $results = Customer::search('acme.com')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('contact@acme.com', $results->first()->email);
    }

    public function test_customer_factory_creates_enterprise_tier()
    {
        $customer = Customer::factory()->enterprise()->create();

        $this->assertEquals('Enterprise Technology', $customer->industry);
        $this->assertStringContainsString('enterprise', strtolower($customer->description));
    }

    public function test_customer_factory_creates_smb_tier()
    {
        $customer = Customer::factory()->smb()->create();

        $this->assertEquals('Small Business', $customer->industry);
        $this->assertStringContainsString('business', strtolower($customer->description));
    }

    public function test_customer_factory_creates_inactive_customer()
    {
        $customer = Customer::factory()->inactive()->create();

        $this->assertFalse($customer->is_active);
    }

    public function test_customer_metadata_is_cast_to_array()
    {
        $metadata = ['key' => 'value', 'number' => 123];
        $customer = Customer::factory()->create(['metadata' => $metadata]);

        $this->assertIsArray($customer->metadata);
        $this->assertEquals($metadata, $customer->metadata);
    }

    public function test_customer_dates_are_cast_correctly()
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $customer->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $customer->updated_at);
    }
}
