<?php

declare(strict_types=1);

namespace App\Domain\Customer\Services;

use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\Events\CustomerUpdated;
use App\Domain\Customer\Models\Customer;
use App\Domain\Organization\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /**
     * Get paginated customers with filtering and sorting.
     */
    public function paginate(int $perPage = 15, array $filters = [], string $sort = 'name', array $includes = []): LengthAwarePaginator
    {
        $query = Customer::query();

        // Apply includes
        if (! empty($includes)) {
            $query->with($includes);
        }

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['industry']) && ! empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%");
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
     * Search customers.
     */
    public function search(string $query, int $perPage = 15, array $includes = []): LengthAwarePaginator
    {
        $customerQuery = Customer::query();

        // Apply includes
        if (! empty($includes)) {
            $customerQuery->with($includes);
        }

        // Apply search
        $customerQuery->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('code', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%")
                ->orWhere('industry', 'like', "%{$query}%");
        });

        return $customerQuery->orderBy('name')->paginate($perPage);
    }

    /**
     * Get customers with incomplete data.
     */
    public function getIncompleteCustomers(int $perPage = 15): LengthAwarePaginator
    {
        return Customer::where(function ($query) {
            $query->whereNull('email')
                ->orWhereNull('phone')
                ->orWhereNull('industry')
                ->orWhereNull('billing_address')
                ->orWhereNull('billing_city')
                ->orWhereNull('billing_country');
        })
            ->with(['contacts', 'locations'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get customer statistics.
     */
    public function getStatistics(): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('is_active', true)->count();
        $inactiveCustomers = Customer::where('is_active', false)->count();
        $customersWithAssets = Customer::has('assets')->count();
        $customersWithoutAssets = Customer::doesntHave('assets')->count();
        $customersWithIncompleteData = Customer::where(function ($query) {
            $query->whereNull('email')
                ->orWhereNull('phone')
                ->orWhereNull('industry')
                ->orWhereNull('billing_address');
        })->count();

        // Industry breakdown
        $industryBreakdown = Customer::whereNotNull('industry')
            ->groupBy('industry')
            ->selectRaw('industry, count(*) as count')
            ->pluck('count', 'industry')
            ->toArray();

        return [
            'total' => $totalCustomers,
            'active' => $activeCustomers,
            'inactive' => $inactiveCustomers,
            'with_assets' => $customersWithAssets,
            'without_assets' => $customersWithoutAssets,
            'incomplete_data' => $customersWithIncompleteData,
            'completion_rate' => $totalCustomers > 0 ? round((($totalCustomers - $customersWithIncompleteData) / $totalCustomers) * 100, 2) : 0,
            'industry_breakdown' => $industryBreakdown,
        ];
    }

    /**
     * Create a new customer.
     */
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            // The organization_id will be handled by the global scope
            // but we need to ensure it's set for creation
            if (! isset($data['organization_id']) && auth()->check()) {
                $data['organization_id'] = auth()->user()->organization_id;
            }

            // Create the customer
            $customer = Customer::create($data);

            // Sync relationships if provided
            if (isset($data['contacts'])) {
                $this->syncContacts($customer, $data['contacts']);
            }

            if (isset($data['statuses'])) {
                $this->syncStatuses($customer, $data['statuses']);
            }

            // Dispatch creation event
            event(new CustomerCreated($customer));

            return $customer->fresh();
        });
    }

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            // Update the customer
            $customer->update($data);

            // Sync relationships if provided
            if (isset($data['contacts'])) {
                $this->syncContacts($customer, $data['contacts']);
            }

            if (isset($data['statuses'])) {
                $this->syncStatuses($customer, $data['statuses']);
            }

            // Dispatch update event
            event(new CustomerUpdated($customer));

            return $customer->fresh();
        });
    }

    /**
     * Soft delete a customer.
     */
    public function delete(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            // Check if customer has active assets
            if ($customer->assets()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'customer' => ['Cannot delete customer with active assets.'],
                ]);
            }

            return $customer->delete();
        });
    }

    /**
     * Restore a soft-deleted customer.
     */
    public function restore(string $id): Customer
    {
        $customer = Customer::withTrashed()->findOrFail($id);

        if (! $customer->trashed()) {
            throw ValidationException::withMessages([
                'customer' => ['Customer is not deleted.'],
            ]);
        }

        $customer->restore();

        return $customer->fresh();
    }

    /**
     * Permanently delete a customer.
     */
    public function forceDelete(string $id): bool
    {
        $customer = Customer::withTrashed()->findOrFail($id);

        // Check if customer has any assets (even inactive)
        if ($customer->assets()->exists()) {
            throw ValidationException::withMessages([
                'customer' => ['Cannot permanently delete customer with assets.'],
            ]);
        }

        return $customer->forceDelete();
    }

    /**
     * Activate a customer.
     */
    public function activate(Customer $customer): Customer
    {
        $customer->update(['is_active' => true]);

        return $customer->fresh();
    }

    /**
     * Deactivate a customer.
     */
    public function deactivate(Customer $customer): Customer
    {
        $customer->update(['is_active' => false]);

        return $customer->fresh();
    }

    /**
     * Bulk activate customers.
     */
    public function bulkActivate(array $customerIds): int
    {
        return Customer::whereIn('id', $customerIds)
            ->update(['is_active' => true]);
    }

    /**
     * Bulk deactivate customers.
     */
    public function bulkDeactivate(array $customerIds): int
    {
        return Customer::whereIn('id', $customerIds)
            ->update(['is_active' => false]);
    }

    /**
     * Sync customer contacts.
     */
    private function syncContacts(Customer $customer, array $contacts): void
    {
        $syncData = [];
        foreach ($contacts as $contact) {
            $syncData[$contact['id']] = [
                'contact_type' => $contact['contact_type'] ?? 'general',
                'is_primary' => $contact['is_primary'] ?? false,
            ];
        }

        $customer->contacts()->sync($syncData);
    }

    /**
     * Sync customer statuses.
     */
    private function syncStatuses(Customer $customer, array $statuses): void
    {
        $syncData = [];
        foreach ($statuses as $status) {
            $syncData[$status['id']] = [
                'is_current' => $status['is_current'] ?? false,
            ];
        }

        $customer->statuses()->sync($syncData);
    }
}
