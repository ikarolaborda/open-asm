<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Organization\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TenantService
{
    /**
     * Get the current organization for the authenticated user.
     */
    public function getCurrentOrganization(): ?Organization
    {
        $user = auth()->user();
        
        if (!$user instanceof User || !$user->organization_id) {
            return null;
        }

        return $user->organization;
    }

    /**
     * Get the current organization ID for the authenticated user.
     */
    public function getCurrentOrganizationId(): ?string
    {
        $user = auth()->user();
        
        if (!$user instanceof User) {
            return null;
        }

        return $user->organization_id;
    }

    /**
     * Check if the current user has access to a specific organization.
     */
    public function hasAccessToOrganization(string $organizationId): bool
    {
        $user = auth()->user();
        
        if (!$user instanceof User) {
            return false;
        }

        // Super admins can access any organization
        if ($user->isSuperAdmin()) {
            return true;
        }

        $currentOrgId = $this->getCurrentOrganizationId();
        
        return $currentOrgId && $currentOrgId === $organizationId;
    }

    /**
     * Check if a model belongs to the current user's organization.
     */
    public function belongsToCurrentOrganization(Model $model): bool
    {
        $user = auth()->user();
        
        if (!$user instanceof User) {
            return false;
        }

        // Super admins can access any organization's data
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!property_exists($model, 'organization_id') && !$model->getAttribute('organization_id')) {
            return false;
        }

        $modelOrgId = $model->getAttribute('organization_id');
        $currentOrgId = $this->getCurrentOrganizationId();

        return $currentOrgId && $modelOrgId === $currentOrgId;
    }

    /**
     * Ensure a model belongs to the current organization or throw an exception.
     */
    public function ensureBelongsToCurrentOrganization(Model $model): void
    {
        if (!$this->belongsToCurrentOrganization($model)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'This resource does not belong to your organization.'
            );
        }
    }

    /**
     * Get organization statistics for the current organization.
     */
    public function getCurrentOrganizationStatistics(): array
    {
        $organization = $this->getCurrentOrganization();
        
        if (!$organization) {
            return [];
        }

        return $organization->getStatistics();
    }

    /**
     * Check if the current organization is active.
     */
    public function isCurrentOrganizationActive(): bool
    {
        $organization = $this->getCurrentOrganization();
        
        return $organization && $organization->isActive();
    }

    /**
     * Set organization_id on a model if not already set.
     */
    public function setOrganizationId(Model $model): void
    {
        $user = auth()->user();
        
        if (!$user instanceof User) {
            return;
        }

        // For super admins, only set organization_id if not already set and user has an organization
        if ($user->isSuperAdmin()) {
            if (!$model->getAttribute('organization_id') && $user->organization_id) {
                $model->setAttribute('organization_id', $user->organization_id);
            }
            return;
        }

        $currentOrgId = $this->getCurrentOrganizationId();
        
        if ($currentOrgId && !$model->getAttribute('organization_id')) {
            $model->setAttribute('organization_id', $currentOrgId);
        }
    }

    /**
     * Check if the current user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        $user = auth()->user();
        
        return $user instanceof User && $user->isSuperAdmin();
    }

    /**
     * Get all organizations that the current user can access.
     */
    public function getAccessibleOrganizations(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        
        if (!$user instanceof User) {
            return collect();
        }

        // Super admins can access all organizations
        if ($user->isSuperAdmin()) {
            return Organization::all();
        }

        // Regular users can only access their own organization
        if ($user->organization_id) {
            return Organization::where('id', $user->organization_id)->get();
        }

        return collect();
    }

    /**
     * Switch organization context for super admins.
     */
    public function switchOrganizationContext(?string $organizationId): bool
    {
        $user = auth()->user();
        
        if (!$user instanceof User || !$user->isSuperAdmin()) {
            return false;
        }

        // Validate organization exists if provided
        if ($organizationId && !Organization::find($organizationId)) {
            return false;
        }

        // This would typically update a session or cache value
        // For now, we'll just return true to indicate permission
        return true;
    }
} 