<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Location\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Locations', description: 'Geographic location management')]
class LocationController extends Controller
{
    #[OA\Get(
        path: '/api/v1/locations',
        summary: 'List all locations',
        description: 'Get a paginated list of all locations with optional filtering',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'filter[name]',
                description: 'Filter by location name',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[customer_id]',
                description: 'Filter by customer ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'filter[city]',
                description: 'Filter by city',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[country]',
                description: 'Filter by country',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[is_headquarters]',
                description: 'Filter by headquarters status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'filter[is_active]',
                description: 'Filter by active status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'sort',
                description: 'Sort by field',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['name', 'city', 'country', 'created_at', '-name', '-city', '-country', '-created_at'])
            ),
            new OA\Parameter(
                name: 'include',
                description: 'Include related resources',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['customer', 'assets'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        'data' => new OA\Property(
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Location')
                        ),
                        'meta' => new OA\Property(ref: '#/components/schemas/PaginationMeta'),
                        'links' => new OA\Property(ref: '#/components/schemas/PaginationLinks'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $locations = QueryBuilder::for(Location::class)
            ->allowedFilters(['name', 'customer_id', 'city', 'country', 'is_headquarters', 'is_active', 'code'])
            ->allowedSorts(['name', 'city', 'country', 'created_at'])
            ->allowedIncludes(['customer', 'assets'])
            ->defaultSort('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($locations);
    }

    #[OA\Post(
        path: '/api/v1/locations',
        summary: 'Create a new location',
        description: 'Create a new geographic location',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    'name' => new OA\Property(type: 'string', description: 'Location name', example: 'New York Office'),
                    'customer_id' => new OA\Property(type: 'string', format: 'uuid', description: 'Customer ID', example: '550e8400-e29b-41d4-a716-446655440000'),
                    'code' => new OA\Property(type: 'string', description: 'Location code', example: 'NYC'),
                    'description' => new OA\Property(type: 'string', description: 'Location description', example: 'Main office in Manhattan'),
                    'address' => new OA\Property(type: 'string', description: 'Street address', example: '123 Broadway'),
                    'city' => new OA\Property(type: 'string', description: 'City', example: 'New York'),
                    'state' => new OA\Property(type: 'string', description: 'State/Province', example: 'NY'),
                    'country' => new OA\Property(type: 'string', description: 'Country', example: 'USA'),
                    'postal_code' => new OA\Property(type: 'string', description: 'Postal code', example: '10001'),
                    'latitude' => new OA\Property(type: 'number', format: 'float', description: 'Latitude', example: 40.7128),
                    'longitude' => new OA\Property(type: 'number', format: 'float', description: 'Longitude', example: -74.0060),
                    'is_headquarters' => new OA\Property(type: 'boolean', description: 'Is headquarters', example: true),
                    'is_active' => new OA\Property(type: 'boolean', description: 'Active status', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Location created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Location')
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'code' => 'nullable|string|max:50|unique:locations,code,NULL,id,organization_id,' . auth()->user()->organization_id,
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_headquarters' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $location = Location::create($validated);

        return response()->json($location->load(['customer']), 201);
    }

    #[OA\Get(
        path: '/api/v1/locations/{id}',
        summary: 'Get location details',
        description: 'Get details of a specific location',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Location ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'include',
                description: 'Include related resources',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['customer', 'assets'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/Location')
            ),
            new OA\Response(response: 404, description: 'Location not found'),
        ]
    )]
    public function show(Request $request, Location $location): JsonResponse
    {
        $includes = $request->get('include', '');
        $allowedIncludes = ['customer', 'assets'];
        $includes = array_intersect(explode(',', $includes), $allowedIncludes);

        if (!empty($includes)) {
            $location->load($includes);
        }

        return response()->json($location);
    }

    #[OA\Put(
        path: '/api/v1/locations/{id}',
        summary: 'Update location',
        description: 'Update an existing location',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Location ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    'name' => new OA\Property(type: 'string', description: 'Location name', example: 'New York Office'),
                    'customer_id' => new OA\Property(type: 'string', format: 'uuid', description: 'Customer ID', example: '550e8400-e29b-41d4-a716-446655440000'),
                    'code' => new OA\Property(type: 'string', description: 'Location code', example: 'NYC'),
                    'description' => new OA\Property(type: 'string', description: 'Location description', example: 'Main office in Manhattan'),
                    'address' => new OA\Property(type: 'string', description: 'Street address', example: '123 Broadway'),
                    'city' => new OA\Property(type: 'string', description: 'City', example: 'New York'),
                    'state' => new OA\Property(type: 'string', description: 'State/Province', example: 'NY'),
                    'country' => new OA\Property(type: 'string', description: 'Country', example: 'USA'),
                    'postal_code' => new OA\Property(type: 'string', description: 'Postal code', example: '10001'),
                    'latitude' => new OA\Property(type: 'number', format: 'float', description: 'Latitude', example: 40.7128),
                    'longitude' => new OA\Property(type: 'number', format: 'float', description: 'Longitude', example: -74.0060),
                    'is_headquarters' => new OA\Property(type: 'boolean', description: 'Is headquarters', example: true),
                    'is_active' => new OA\Property(type: 'boolean', description: 'Active status', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Location updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Location')
            ),
            new OA\Response(response: 404, description: 'Location not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'code' => 'nullable|string|max:50|unique:locations,code,' . $location->id . ',id,organization_id,' . auth()->user()->organization_id,
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_headquarters' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        return response()->json($location->load(['customer']));
    }

    #[OA\Delete(
        path: '/api/v1/locations/{id}',
        summary: 'Delete location',
        description: 'Delete a location (soft delete)',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Location ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Location deleted successfully'),
            new OA\Response(response: 404, description: 'Location not found'),
        ]
    )]
    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        return response()->json(null, 204);
    }

    #[OA\Get(
        path: '/api/v1/locations/{id}/assets',
        summary: 'Get location assets',
        description: 'Get all assets at a specific location',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Location ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        'data' => new OA\Property(
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Asset')
                        ),
                        'meta' => new OA\Property(ref: '#/components/schemas/PaginationMeta'),
                        'links' => new OA\Property(ref: '#/components/schemas/PaginationLinks'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Location not found'),
        ]
    )]
    public function assets(Request $request, Location $location): JsonResponse
    {
        $assets = $location->assets()
            ->paginate($request->get('per_page', 15));

        return response()->json($assets);
    }
} 