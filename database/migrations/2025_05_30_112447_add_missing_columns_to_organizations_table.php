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
            // Add timezone and currency columns that might be missing
            if (! Schema::hasColumn('organizations', 'timezone')) {
                $table->string('timezone')->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('organizations', 'currency')) {
                $table->string('currency', 3)->nullable()->after('timezone');
            }
            if (! Schema::hasColumn('organizations', 'domain')) {
                $table->string('domain')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'currency', 'domain']);
        });
    }
};
