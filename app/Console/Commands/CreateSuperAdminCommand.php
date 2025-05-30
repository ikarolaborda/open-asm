<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'user:create-superadmin
                            {email : The email address of the new super-admin}
                            {--name= : Full name of the new super-admin}
                            {--password= : Password (will prompt if omitted)}
                            {--organization_id= : UUID of the organization (optional)}';

    protected $description = 'Create a new super-admin user and give them the super-admin role with all API-guard permissions';

    public function handle(): int
    {
        $email    = $this->argument('email');
        $name     = $this->option('name') ?: $this->ask('Full name');
        $password = $this->option('password') ?: $this->secret('Password (hidden)');

        if (! $password) {
            $this->error('A password is required.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email {$email} already exists.");

            return self::FAILURE;
        }

        // Check if permissions exist, if not, suggest running the seeder
        $apiPermissions = Permission::where('guard_name', 'api')->get();
        if ($apiPermissions->isEmpty()) {
            $this->error('No API-guard permissions found. Please run: php artisan db:seed --class=RolesAndPermissionsSeeder');

            return self::FAILURE;
        }

        $orgId = $this->option('organization_id');

        $user = User::create([
            'name'            => $name,
            'email'           => $email,
            'password'        => Hash::make($password),
            'is_active'       => true,
            'organization_id' => $orgId,
        ]);

        // Get or create the super-admin role (it should already exist from seeder)
        $role = Role::where('name', 'super-admin')
            ->where('guard_name', 'api')
            ->first();

        if (! $role) {
            $this->warn('Super-admin role not found. Creating it now...');
            $role = Role::create(['name' => 'super-admin', 'guard_name' => 'api']);
            $role->syncPermissions($apiPermissions);
        }

        // Assign the role to the user
        $user->assignRole($role);

        // Optionally sync permissions directly to user (redundant but ensures super admin has all permissions)
        $user->syncPermissions($apiPermissions);

        $this->info("Super-admin {$email} created successfully with {$apiPermissions->count()} API-guard permissions.");
        $this->info("Assigned role: {$role->name}");

        return self::SUCCESS;
    }
}
