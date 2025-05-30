<?php

declare(strict_types=1);

namespace App\Domain\Organization\Services;

use App\Domain\Organization\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Create a new organization.
     */
    public function create(array $data): Organization
    {
        return DB::transaction(function () use ($data) {
            $organization = Organization::create($data);

            // Log the organization creation
            activity()
                ->performedOn($organization)
                ->event('organization_created')
                ->log('Organization created');

            return $organization->fresh();
        });
    }

    /**
     * Update an existing organization.
     */
    public function update(Organization $organization, array $data): Organization
    {
        return DB::transaction(function () use ($organization, $data) {
            $organization->update($data);

            // Log the organization update
            activity()
                ->performedOn($organization)
                ->event('organization_updated')
                ->log('Organization updated');

            return $organization->fresh();
        });
    }

    /**
     * Get organization statistics.
     */
    public function getStatistics(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'code' => $organization->code,
            'is_active' => $organization->is_active,
            'created_at' => $organization->created_at,
            'statistics' => $organization->getStatistics(),
        ];
    }

    /**
     * Get detailed organization overview with related data.
     */
    public function getOverview(Organization $organization): array
    {
        $statistics = $organization->getStatistics();

        return [
            'organization' => $organization,
            'statistics' => $statistics,
            'recent_customers' => $organization->customers()
                ->latest()
                ->limit(5)
                ->get(),
            'recent_assets' => $organization->assets()
                ->latest()
                ->limit(5)
                ->get(),
            'active_users' => $organization->users()
                ->where('is_active', true)
                ->count(),
        ];
    }

    /**
     * Activate an organization and all its related entities.
     */
    public function activate(Organization $organization): void
    {
        DB::transaction(function () use ($organization) {
            $organization->activate();

            // Log the activation
            activity()
                ->performedOn($organization)
                ->event('organization_activated')
                ->log('Organization activated');
        });
    }

    /**
     * Deactivate an organization and optionally its related entities.
     */
    public function deactivate(Organization $organization, bool $deactivateUsers = false): void
    {
        DB::transaction(function () use ($organization, $deactivateUsers) {
            $organization->deactivate();

            if ($deactivateUsers) {
                $organization->users()->update(['is_active' => false]);
            }

            // Log the deactivation
            activity()
                ->performedOn($organization)
                ->event('organization_deactivated')
                ->log('Organization deactivated');
        });
    }

    /**
     * Get organizations with pagination and filtering.
     */
    public function paginate(int $perPage = 15, array $filters = [], string $sort = 'name'): LengthAwarePaginator
    {
        $query = Organization::query();

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Apply sorting
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $allowedSortFields = ['name', 'code', 'email', 'created_at', 'updated_at', 'is_active'];
        if (in_array($field, $allowedSortFields)) {
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Validate organization code uniqueness.
     */
    public function isCodeUnique(string $code, ?string $excludeId = null): bool
    {
        $query = Organization::where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Get organization health status.
     */
    public function getHealthStatus(Organization $organization): array
    {
        $statistics = $organization->getStatistics();

        return [
            'overall_health' => $this->calculateHealthScore($statistics),
            'checks' => [
                'is_active' => $organization->is_active,
                'has_users' => $statistics['users_count'] > 0,
                'has_customers' => $statistics['customers_count'] > 0,
                'has_assets' => $statistics['assets_count'] > 0,
                'active_ratio' => $statistics['assets_count'] > 0
                    ? round(($statistics['active_assets_count'] / $statistics['assets_count']) * 100, 2)
                    : 100,
            ],
            'recommendations' => $this->getHealthRecommendations($statistics),
        ];
    }

    /**
     * Calculate health score based on organization statistics.
     */
    private function calculateHealthScore(array $statistics): string
    {
        $score = 0;

        if ($statistics['users_count'] > 0) {
            $score += 25;
        }
        if ($statistics['customers_count'] > 0) {
            $score += 25;
        }
        if ($statistics['assets_count'] > 0) {
            $score += 25;
        }
        if ($statistics['assets_count'] > 0 &&
            ($statistics['active_assets_count'] / $statistics['assets_count']) > 0.8) {
            $score += 25;
        }

        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'fair',
            default => 'needs_attention'
        };
    }

    /**
     * Get health recommendations based on statistics.
     */
    private function getHealthRecommendations(array $statistics): array
    {
        $recommendations = [];

        if ($statistics['users_count'] === 0) {
            $recommendations[] = 'Add users to this organization';
        }

        if ($statistics['customers_count'] === 0) {
            $recommendations[] = 'Add customers to start managing assets';
        }

        if ($statistics['assets_count'] === 0) {
            $recommendations[] = 'Begin adding assets to track';
        }

        if ($statistics['assets_count'] > 0) {
            $activeRatio = $statistics['active_assets_count'] / $statistics['assets_count'];
            if ($activeRatio < 0.8) {
                $recommendations[] = 'Review inactive assets - consider reactivating or retiring';
            }
        }

        return $recommendations;
    }
}
