<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create customers table
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('code')->nullable(); // Customer reference code
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->text('description')->nullable();

            // Billing address fields
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_postal_code')->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['organization_id', 'code']);
            $table->index(['organization_id', 'is_active']);
            $table->unique(['organization_id', 'code']);
        });

        // Create assets table
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('customer_id');
            $table->uuid('location_id')->nullable();
            $table->uuid('oem_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('type_id')->nullable();
            $table->uuid('status_id')->nullable();

            // Asset identification
            $table->string('serial_number');
            $table->string('asset_tag')->nullable();
            $table->string('model_number')->nullable();
            $table->string('part_number')->nullable();

            // Asset details
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();

            // Status and tracking
            $table->boolean('is_active')->default(true);
            $table->integer('data_quality_score')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('oem_id')->references('id')->on('oems')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('set null');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('set null');

            // Indexes
            $table->index(['organization_id', 'customer_id']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['organization_id', 'serial_number']);
            $table->unique(['organization_id', 'serial_number']);
        });

        // Create asset_warranties table
        Schema::create('asset_warranties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->uuid('coverage_id')->nullable();
            $table->uuid('service_level_id')->nullable();

            $table->string('warranty_type'); // manufacturer, extended, service_contract
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('provider')->nullable();
            $table->string('contract_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('coverage_id')->references('id')->on('coverages')->onDelete('set null');
            $table->foreign('service_level_id')->references('id')->on('service_levels')->onDelete('set null');

            // Indexes
            $table->index(['asset_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_warranties');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('customers');
    }
};
