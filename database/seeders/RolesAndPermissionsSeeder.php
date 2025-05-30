<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Organization management
            'view-organization',
            'edit-organization',
            'manage-organization-users',
            'view-organization-statistics',
            'view-organization-health',

            // Asset management
            'view-assets',
            'create-assets',
            'edit-assets',
            'delete-assets',
            'retire-assets',
            'reactivate-assets',
            'manage-asset-warranties',
            'view-asset-statistics',
            'bulk-update-assets',

            // Customer management
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',
            'activate-customers',
            'deactivate-customers',
            'manage-customer-contacts',
            'view-customer-statistics',
            'bulk-update-customers',

            // User management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'assign-roles',
            'manage-user-permissions',

            // Super admin permissions
            'access-all-organizations',
            'manage-system-settings',
            'view-system-logs',

            // Location management
            'view-locations',
            'create-locations',
            'edit-locations',
            'delete-locations',

            // Shared resources management
            'manage-contacts',
            'manage-oems',
            'manage-products',
            'manage-types',
            'manage-statuses',
            'manage-tags',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin Role - Can access everything across all organizations
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Admin Role - Full access within their organization
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            // Organization management (own org only)
            'view-organization',
            'edit-organization',
            'manage-organization-users',
            'view-organization-statistics',
            'view-organization-health',

            // Full asset management
            'view-assets',
            'create-assets',
            'edit-assets',
            'delete-assets',
            'retire-assets',
            'reactivate-assets',
            'manage-asset-warranties',
            'view-asset-statistics',
            'bulk-update-assets',

            // Full customer management
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',
            'activate-customers',
            'deactivate-customers',
            'manage-customer-contacts',
            'view-customer-statistics',
            'bulk-update-customers',

            // User management within organization
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'assign-roles',

            // Location management
            'view-locations',
            'create-locations',
            'edit-locations',
            'delete-locations',

            // Shared resources
            'manage-contacts',
            'manage-oems',
            'manage-products',
            'manage-types',
            'manage-statuses',
            'manage-tags',
        ]);

        // User Role - Limited access for asset operations
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo([
            // Organization viewing only
            'view-organization',
            'view-organization-statistics',

            // Limited asset management
            'view-assets',
            'create-assets',
            'edit-assets',
            'retire-assets',
            'reactivate-assets',
            'manage-asset-warranties',
            'view-asset-statistics',

            // Customer viewing and limited editing
            'view-customers',
            'edit-customers',
            'manage-customer-contacts',
            'view-customer-statistics',

            // Location viewing and limited management
            'view-locations',
            'create-locations',
            'edit-locations',

            // Shared resources viewing
            'manage-contacts',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: super-admin, admin, user');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
} 