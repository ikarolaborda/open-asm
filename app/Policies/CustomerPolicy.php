<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Customer\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-customers');
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->can('view-customers') && 
               $user->organization_id === $customer->organization_id;
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->can('create-customers');
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->can('edit-customers') && 
               $user->organization_id === $customer->organization_id;
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('delete-customers') && 
               $user->organization_id === $customer->organization_id;
    }

    /**
     * Determine whether the user can restore the customer.
     */
    public function restore(User $user, Customer $customer): bool
    {
        return $user->can('delete-customers') && 
               $user->organization_id === $customer->organization_id;
    }

    /**
     * Determine whether the user can permanently delete the customer.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->hasRole('super-admin') && 
               $user->can('delete-customers') && 
               $user->organization_id === $customer->organization_id;
    }

    /**
     * Determine whether the user can activate the customer.
     */
    public function activate(User $user, Customer $customer): bool
    {
        return $user->can('activate-customers') && 
               $user->organization_id === $customer->organization_id;
    }

    /**
     * Determine whether the user can deactivate the customer.
     */
    public function deactivate(User $user, Customer $customer): bool
    {
        return $user->can('deactivate-customers') && 
               $user->organization_id === $customer->organization_id;
    }

    /**
     * Determine whether the user can view customer statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->can('view-customer-statistics');
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkOperations(User $user): bool
    {
        return $user->can('bulk-update-customers') || 
               $user->can('activate-customers') || 
               $user->can('deactivate-customers') ||
               $user->can('delete-customers');
    }

    /**
     * Determine whether the user can view customer assets.
     */
    public function viewAssets(User $user, Customer $customer): bool
    {
        return $user->can('view-assets') && 
               $user->organization_id === $customer->organization_id;
    }

    /**
     * Determine whether the user can view customer locations.
     */
    public function viewLocations(User $user, Customer $customer): bool
    {
        return $user->can('view-locations') && 
               $user->organization_id === $customer->organization_id;
    }
} 