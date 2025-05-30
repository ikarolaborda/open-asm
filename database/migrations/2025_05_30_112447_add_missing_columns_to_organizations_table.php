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
        Schema::table('organizations', function (Blueprint $table) {
            // Add missing columns that are in the model's fillable array
            if (! Schema::hasColumn('organizations', 'code')) {
                $table->string('code')->nullable()->after('name');
            }
            if (! Schema::hasColumn('organizations', 'email')) {
                $table->string('email')->nullable()->after('code');
            }
            if (! Schema::hasColumn('organizations', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (! Schema::hasColumn('organizations', 'website')) {
                $table->string('website')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('organizations', 'description')) {
                $table->text('description')->nullable()->after('website');
            }
            if (! Schema::hasColumn('organizations', 'address')) {
                $table->string('address')->nullable()->after('description');
            }
            if (! Schema::hasColumn('organizations', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (! Schema::hasColumn('organizations', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (! Schema::hasColumn('organizations', 'country')) {
                $table->string('country')->nullable()->after('state');
            }
            if (! Schema::hasColumn('organizations', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
            if (! Schema::hasColumn('organizations', 'metadata')) {
                $table->json('metadata')->nullable()->after('is_active');
            }

            // Add indexes for performance
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'code', 'email', 'phone', 'website', 'description',
                'address', 'city', 'state', 'country', 'postal_code', 'metadata',
            ]);
        });
    }
};
