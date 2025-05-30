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
        // Create customer_contacts pivot table
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->uuid('customer_id');
            $table->uuid('contact_id');
            $table->string('contact_type')->default('general'); // primary, billing, technical, general
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->primary(['customer_id', 'contact_id']);
            $table->index(['customer_id', 'is_primary']);
        });

        // Create customer_statuses pivot table
        Schema::create('customer_statuses', function (Blueprint $table) {
            $table->uuid('customer_id');
            $table->uuid('status_id');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade');
            $table->primary(['customer_id', 'status_id']);
            $table->index(['customer_id', 'is_current']);
        });

        // Create asset_tags pivot table
        Schema::create('asset_tags', function (Blueprint $table) {
            $table->uuid('asset_id');
            $table->uuid('tag_id');
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->primary(['asset_id', 'tag_id']);
        });

        // Create asset_contacts pivot table
        Schema::create('asset_contacts', function (Blueprint $table) {
            $table->uuid('asset_id');
            $table->uuid('contact_id');
            $table->string('contact_type')->default('owner'); // owner, technical, manager
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->primary(['asset_id', 'contact_id']);
            $table->index(['asset_id', 'is_primary']);
        });

        // Create location_contacts pivot table
        Schema::create('location_contacts', function (Blueprint $table) {
            $table->uuid('location_id');
            $table->uuid('contact_id');
            $table->string('contact_type')->default('site'); // site, manager, security
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->primary(['location_id', 'contact_id']);
            $table->index(['location_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_contacts');
        Schema::dropIfExists('asset_contacts');
        Schema::dropIfExists('asset_tags');
        Schema::dropIfExists('customer_statuses');
        Schema::dropIfExists('customer_contacts');
    }
};
