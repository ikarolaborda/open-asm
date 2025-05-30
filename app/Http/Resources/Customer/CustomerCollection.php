<?php

declare(strict_types=1);

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     */
    public $collects = CustomerResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'has_more_pages' => $this->hasMorePages(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
                'self' => $this->url($this->currentPage()),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'timestamp' => now()->toISOString(),
                'filters' => $this->getAppliedFilters($request),
                'sorting' => $this->getAppliedSorting($request),
            ],
        ];
    }

    /**
     * Get the applied filters from the request.
     */
    private function getAppliedFilters(Request $request): array
    {
        $filters = [];

        if ($request->has('filter')) {
            $requestFilters = $request->get('filter', []);

            if (isset($requestFilters['is_active'])) {
                $filters['is_active'] = $requestFilters['is_active'];
            }

            if (isset($requestFilters['industry'])) {
                $filters['industry'] = $requestFilters['industry'];
            }

            if (isset($requestFilters['search'])) {
                $filters['search'] = $requestFilters['search'];
            }
        }

        return $filters;
    }

    /**
     * Get the applied sorting from the request.
     */
    private function getAppliedSorting(Request $request): array
    {
        $sort = $request->get('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        return [
            'field' => $field,
            'direction' => $direction,
        ];
    }
}
