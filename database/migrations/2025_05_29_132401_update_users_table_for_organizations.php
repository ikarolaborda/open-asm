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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('organization_id')->nullable()->after('id');
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('title')->nullable()->after('phone');
            $table->string('department')->nullable()->after('title');
            $table->boolean('is_active')->default(true)->after('department');
            $table->json('metadata')->nullable()->after('is_active');
            $table->softDeletes()->after('updated_at');

            // Add foreign key constraint
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['organization_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id', 'is_active']);
            $table->dropColumn([
                'organization_id',
                'first_name',
                'last_name',
                'phone',
                'title',
                'department',
                'is_active',
                'metadata',
                'deleted_at',
            ]);
        });
    }
};
