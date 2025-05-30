<?php

declare(strict_types=1);

namespace App\Domain\Asset\Services;

use App\Domain\Asset\Models\Asset;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetService
{
    /**
     * Get paginated assets with filtering and sorting.
     */
    public function paginate(int $perPage = 15, array $filters = [], string $sort = 'name', array $includes = []): LengthAwarePaginator
    {
        $query = Asset::query();

        // Apply includes
        if (! empty($includes)) {
            $query->with($includes);
        }

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['customer_id']) && ! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['location_id']) && ! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['type_id']) && ! empty($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }

        if (isset($filters['warranty_status'])) {
            switch ($filters['warranty_status']) {
                case 'expiring_soon':
                    $query->warrantyExpiringSoon();
                    break;
                case 'expired':
                    $query->warrantyExpired();
                    break;
            }
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply sorting
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $allowedSortFields = ['name', 'serial_number', 'asset_tag', 'purchase_date', 'created_at', 'updated_at', 'is_active', 'data_quality_score'];
        if (in_array($field, $allowedSortFields)) {
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Search assets.
     */
    public function search(string $query, int $perPage = 15, array $includes = []): LengthAwarePaginator
    {
        $assetQuery = Asset::query();

        // Apply includes
        if (! empty($includes)) {
            $assetQuery->with($includes);
        }

        // Apply search
        $assetQuery->search($query);

        return $assetQuery->orderBy('name')->paginate($perPage);
    }

    /**
     * Get assets with low data quality scores.
     */
    public function getLowQualityAssets(int $threshold = 70, int $perPage = 15): LengthAwarePaginator
    {
        return Asset::where('data_quality_score', '<', $threshold)
            ->with(['customer', 'type', 'location'])
            ->orderBy('data_quality_score', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get assets with warranties expiring soon.
     */
    public function getAssetsWithExpiringWarranties(int $days = 30, int $perPage = 15): LengthAwarePaginator
    {
        return Asset::warrantyExpiringSoon()
            ->with(['customer', 'warranties'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get assets with expired warranties.
     */
    public function getAssetsWithExpiredWarranties(int $perPage = 15): LengthAwarePaginator
    {
        return Asset::warrantyExpired()
            ->with(['customer', 'warranties'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get asset statistics.
     */
    public function getStatistics(): array
    {
        $totalAssets = Asset::count();
        $activeAssets = Asset::where('is_active', true)->count();
        $retiredAssets = Asset::where('is_active', false)->count();
        $assetsWithWarranty = Asset::whereHas('warranties', function ($q) {
            $q->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now());
        })->count();
        $assetsWithoutWarranty = $totalAssets - $assetsWithWarranty;
        $lowQualityAssets = Asset::where('data_quality_score', '<', 70)->count();

        // Warranty statistics
        $warrantyExpiringSoon = Asset::warrantyExpiringSoon()->count();
        $warrantyExpired = Asset::warrantyExpired()->count();

        // Type breakdown
        $typeBreakdown = Asset::whereNotNull('assets.type_id')
            ->join('types', 'assets.type_id', '=', 'types.id')
            ->groupBy('types.name')
            ->selectRaw('types.name, count(*) as count')
            ->pluck('count', 'name')
            ->toArray();

        // Customer breakdown (top 10)
        $customerBreakdown = Asset::join('customers', 'assets.customer_id', '=', 'customers.id')
            ->groupBy('customers.name')
            ->selectRaw('customers.name, count(*) as count')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'name')
            ->toArray();

        return [
            'total' => $totalAssets,
            'active' => $activeAssets,
            'retired' => $retiredAssets,
            'with_warranty' => $assetsWithWarranty,
            'without_warranty' => $assetsWithoutWarranty,
            'low_quality' => $lowQualityAssets,
            'warranty_expiring_soon' => $warrantyExpiringSoon,
            'warranty_expired' => $warrantyExpired,
            'average_quality_score' => $totalAssets > 0 ? round(Asset::avg('data_quality_score'), 2) : 0,
            'type_breakdown' => $typeBreakdown,
            'top_customers' => $customerBreakdown,
        ];
    }

    /**
     * Create a new asset.
     */
    public function create(array $data): Asset
    {
        return DB::transaction(function () use ($data) {
            // The organization_id will be handled by the global scope
            // but we need to ensure it's set for creation
            if (! isset($data['organization_id']) && auth()->check()) {
                $data['organization_id'] = auth()->user()->organization_id;
            }

            // Create the asset
            $asset = Asset::create($data);

            // Sync tags if provided
            if (isset($data['tags'])) {
                $asset->tags()->sync($data['tags']);
            }

            return $asset->fresh();
        });
    }

    /**
     * Update an existing asset.
     */
    public function update(Asset $asset, array $data): Asset
    {
        return DB::transaction(function () use ($asset, $data) {
            // Update the asset
            $asset->update($data);

            // Sync tags if provided
            if (isset($data['tags'])) {
                $asset->tags()->sync($data['tags']);
            }

            return $asset->fresh();
        });
    }

    /**
     * Soft delete an asset.
     */
    public function delete(Asset $asset): bool
    {
        return $asset->delete();
    }

    /**
     * Restore a soft-deleted asset.
     */
    public function restore(string $id): Asset
    {
        $asset = Asset::withTrashed()->findOrFail($id);

        if (! $asset->trashed()) {
            throw ValidationException::withMessages([
                'asset' => ['Asset is not deleted.'],
            ]);
        }

        $asset->restore();

        return $asset->fresh();
    }

    /**
     * Permanently delete an asset.
     */
    public function forceDelete(string $id): bool
    {
        $asset = Asset::withTrashed()->findOrFail($id);

        return $asset->forceDelete();
    }

    /**
     * Retire an asset.
     */
    public function retire(Asset $asset): Asset
    {
        $asset->retire();

        return $asset->fresh();
    }

    /**
     * Reactivate an asset.
     */
    public function reactivate(Asset $asset): Asset
    {
        $asset->reactivate();

        return $asset->fresh();
    }

    /**
     * Calculate and update data quality score for an asset.
     */
    public function calculateDataQuality(Asset $asset): Asset
    {
        $asset->data_quality_score = $asset->calculateDataQualityScore();
        $asset->save();

        return $asset->fresh();
    }

    /**
     * Bulk update assets.
     */
    public function bulkUpdate(array $assetIds, array $data): int
    {
        return Asset::whereIn('id', $assetIds)->update($data);
    }

    /**
     * Bulk retire assets.
     */
    public function bulkRetire(array $assetIds): int
    {
        return $this->bulkUpdate($assetIds, ['is_active' => false]);
    }

    /**
     * Bulk reactivate assets.
     */
    public function bulkReactivate(array $assetIds): int
    {
        return $this->bulkUpdate($assetIds, ['is_active' => true]);
    }
}
