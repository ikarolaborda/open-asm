<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Asset\Models\Asset;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assets.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-assets');
    }

    /**
     * Determine whether the user can view the asset.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $user->can('view-assets') &&
               $user->organization_id === $asset->organization_id;
    }

    /**
     * Determine whether the user can create assets.
     */
    public function create(User $user): bool
    {
        return $user->can('create-assets');
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, Asset $asset): bool
    {
        return $user->can('edit-assets') &&
               $user->organization_id === $asset->organization_id;
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, Asset $asset): bool
    {
        return $user->can('delete-assets') &&
               $user->organization_id === $asset->organization_id;
    }

    /**
     * Determine whether the user can restore the asset.
     */
    public function restore(User $user, Asset $asset): bool
    {
        return $user->can('delete-assets') &&
               $user->organization_id === $asset->organization_id;
    }

    /**
     * Determine whether the user can permanently delete the asset.
     */
    public function forceDelete(User $user, Asset $asset): bool
    {
        return $user->hasRole('super-admin') &&
               $user->can('delete-assets') &&
               $user->organization_id === $asset->organization_id;
    }

    /**
     * Determine whether the user can retire the asset.
     */
    public function retire(User $user, Asset $asset): bool
    {
        return $user->can('retire-assets') &&
               $user->organization_id === $asset->organization_id;
    }

    /**
     * Determine whether the user can reactivate the asset.
     */
    public function reactivate(User $user, Asset $asset): bool
    {
        return $user->can('reactivate-assets') &&
               $user->organization_id === $asset->organization_id;
    }

    /**
     * Determine whether the user can manage asset warranties.
     */
    public function manageWarranties(User $user, Asset $asset): bool
    {
        return $user->can('manage-asset-warranties') &&
               $user->organization_id === $asset->organization_id;
    }

    /**
     * Determine whether the user can view asset statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->can('view-asset-statistics');
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkOperations(User $user): bool
    {
        return $user->can('bulk-update-assets') || $user->can('delete-assets');
    }
}
