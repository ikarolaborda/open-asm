<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Services\CustomerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\Customer\CustomerCollection;
use App\Http\Resources\Customer\CustomerResource;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Customers', description: 'Customer management endpoints')]
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
        private LoggingService $loggingService
    ) {}

    #[OA\Get(
        path: '/api/v1/customers',
        summary: 'Get paginated list of customers',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                description: 'Sort field (prefix with - for descending)',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'name')
            ),
            new OA\Parameter(
                name: 'filter[is_active]',
                in: 'query',
                description: 'Filter by active status',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'filter[customer_code]',
                in: 'query',
                description: 'Filter by customer code',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomerCollection')
            ),
        ]
    )]
    public function index(Request $request): CustomerCollection
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        $customers = $this->customerService->paginate(
            perPage: (int) $request->get('per_page', 15),
            filters: $request->get('filter', []),
            sort: $request->get('sort', 'name'),
            includes: $request->get('include', [])
        );

        $this->loggingService->logBusinessOperation('list', 'customer', 'multiple');

        return new CustomerCollection($customers);
    }

    #[OA\Post(
        path: '/api/v1/customers',
        summary: 'Create a new customer',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateCustomerRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Customer created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/CustomerResource'),
                    ]
                )
            ),
        ]
    )]
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        try {
            $customer = $this->customerService->create($request->validated());

            $this->loggingService->logBusinessOperation('create', 'customer', $customer->id);

            return response()->json([
                'message' => 'Customer created successfully.',
                'data' => new CustomerResource($customer->load(['organization', 'contacts', 'statuses'])),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to create customer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create customer.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/api/v1/customers/{customer}',
        summary: 'Get a specific customer',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                description: 'Customer ID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomerResource')
            ),
        ]
    )]
    public function show(Request $request, Customer $customer): CustomerResource
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        $includes = $request->get('include', []);
        if (! empty($includes)) {
            $customer->load($includes);
        }

        $this->loggingService->logBusinessOperation('view', 'customer', $customer->id);

        return new CustomerResource($customer);
    }

    #[OA\Put(
        path: '/api/v1/customers/{customer}',
        summary: 'Update a customer',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                description: 'Customer ID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateCustomerRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/CustomerResource'),
                    ]
                )
            ),
        ]
    )]
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        try {
            $updatedCustomer = $this->customerService->update($customer, $request->validated());

            $this->loggingService->logBusinessOperation('update', 'customer', $customer->id);

            return response()->json([
                'message' => 'Customer updated successfully.',
                'data' => new CustomerResource($updatedCustomer->load(['organization', 'contacts', 'statuses'])),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to update customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update customer.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/api/v1/customers/{customer}',
        summary: 'Delete a customer (soft delete)',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                description: 'Customer ID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $this->customerService->delete($customer);

            $this->loggingService->logBusinessOperation('delete', 'customer', $customer->id);

            return response()->json([
                'message' => 'Customer deleted successfully.',
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to delete customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to delete customer.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/api/v1/customers/{id}/restore',
        summary: 'Restore a soft-deleted customer',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Customer ID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer restored successfully'
            ),
        ]
    )]
    public function restore(string $id): JsonResponse
    {
        try {
            $customer = $this->customerService->restore($id);

            $this->loggingService->logBusinessOperation('restore', 'customer', $id);

            return response()->json([
                'message' => 'Customer restored successfully.',
                'data' => new CustomerResource($customer),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to restore customer', [
                'customer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to restore customer.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/api/v1/customers/{id}/force',
        summary: 'Permanently delete a customer',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Customer ID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer permanently deleted'
            ),
        ]
    )]
    public function forceDestroy(string $id): JsonResponse
    {
        try {
            $this->customerService->forceDelete($id);

            $this->loggingService->logBusinessOperation('force_delete', 'customer', $id);

            return response()->json([
                'message' => 'Customer permanently deleted.',
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to force delete customer', [
                'customer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to permanently delete customer.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Patch(
        path: '/api/v1/customers/{customer}/activate',
        summary: 'Activate a customer',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                description: 'Customer ID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer activated successfully'
            ),
        ]
    )]
    public function activate(Customer $customer): JsonResponse
    {
        try {
            $this->customerService->activate($customer);

            $this->loggingService->logBusinessOperation('activate', 'customer', $customer->id);

            return response()->json([
                'message' => 'Customer activated successfully.',
                'data' => new CustomerResource($customer->fresh()),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to activate customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to activate customer.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Patch(
        path: '/api/v1/customers/{customer}/deactivate',
        summary: 'Deactivate a customer',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                description: 'Customer ID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer deactivated successfully'
            ),
        ]
    )]
    public function deactivate(Customer $customer): JsonResponse
    {
        try {
            $this->customerService->deactivate($customer);

            $this->loggingService->logBusinessOperation('deactivate', 'customer', $customer->id);

            return response()->json([
                'message' => 'Customer deactivated successfully.',
                'data' => new CustomerResource($customer->fresh()),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to deactivate customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to deactivate customer.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/api/v1/customers/bulk/activate',
        summary: 'Bulk activate customers',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'customer_ids',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'uuid'),
                        description: 'Array of customer IDs to activate'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customers activated successfully'
            ),
        ]
    )]
    public function bulkActivate(Request $request): JsonResponse
    {
        $request->validate([
            'customer_ids' => ['required', 'array'],
            'customer_ids.*' => ['required', 'uuid', 'exists:customers,id'],
        ]);

        try {
            $count = $this->customerService->bulkActivate($request->input('customer_ids'));

            $this->loggingService->logBusinessOperation('bulk_activate', 'customer', 'multiple', [
                'count' => $count,
                'customer_ids' => $request->input('customer_ids'),
            ]);

            return response()->json([
                'message' => "Successfully activated {$count} customers.",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to bulk activate customers', [
                'customer_ids' => $request->input('customer_ids'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to activate customers.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/api/v1/customers/bulk/deactivate',
        summary: 'Bulk deactivate customers',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'customer_ids',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'uuid'),
                        description: 'Array of customer IDs to deactivate'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customers deactivated successfully'
            ),
        ]
    )]
    public function bulkDeactivate(Request $request): JsonResponse
    {
        $request->validate([
            'customer_ids' => ['required', 'array'],
            'customer_ids.*' => ['required', 'uuid', 'exists:customers,id'],
        ]);

        try {
            $count = $this->customerService->bulkDeactivate($request->input('customer_ids'));

            $this->loggingService->logBusinessOperation('bulk_deactivate', 'customer', 'multiple', [
                'count' => $count,
                'customer_ids' => $request->input('customer_ids'),
            ]);

            return response()->json([
                'message' => "Successfully deactivated {$count} customers.",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to bulk deactivate customers', [
                'customer_ids' => $request->input('customer_ids'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to deactivate customers.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/api/v1/customers/incomplete-data',
        summary: 'Get customers with incomplete data',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customers with incomplete data'
            ),
        ]
    )]
    public function incompleteData(Request $request): CustomerCollection
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        $customers = $this->customerService->getIncompleteCustomers(
            perPage: (int) $request->get('per_page', 15)
        );

        $this->loggingService->logBusinessOperation('list_incomplete', 'customer', 'multiple');

        return new CustomerCollection($customers);
    }

    #[OA\Get(
        path: '/api/v1/customers/statistics',
        summary: 'Get customer statistics',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer statistics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'active', type: 'integer'),
                                new OA\Property(property: 'inactive', type: 'integer'),
                                new OA\Property(property: 'incomplete_data', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->customerService->getStatistics();

            $this->loggingService->logBusinessOperation('statistics', 'customer', 'aggregate');

            return response()->json([
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to get customer statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve statistics.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/api/v1/customers/search',
        summary: 'Search customers',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                description: 'Search query',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 2)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results'
            ),
        ]
    )]
    public function search(Request $request): CustomerCollection
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['integer', 'min:1', 'max:100'],
        ]);

        $this->loggingService->logApiRequest($request->method(), $request->path());

        $customers = $this->customerService->search(
            query: $request->input('q'),
            perPage: (int) $request->get('per_page', 15),
            includes: $request->get('include', [])
        );

        $this->loggingService->logBusinessOperation('search', 'customer', 'multiple', [
            'query' => $request->input('q'),
        ]);

        return new CustomerCollection($customers);
    }
}
