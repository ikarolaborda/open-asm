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
        description: 'Get a paginated list of all locations with optional filtering',
        summary: 'List all locations',
        security: [['bearerAuth' => []]],
        tags: ['Locations'],
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
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'city', type: 'string'),
                                    new OA\Property(property: 'country', type: 'string'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
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
        description: 'Create a new geographic location',
        summary: 'Create a new location',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    'name' => new OA\Property(property: 'name', description: 'Location name', type: 'string', example: 'New York Office'),
                    'customer_id' => new OA\Property(property: 'customer_id', description: 'Customer ID', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                    'code' => new OA\Property(property: 'code', description: 'Location code', type: 'string', example: 'NYC'),
                    'description' => new OA\Property(property: 'description', description: 'Location description', type: 'string', example: 'Main office in Manhattan'),
                    'address' => new OA\Property(property: 'address', description: 'Street address', type: 'string', example: '123 Broadway'),
                    'city' => new OA\Property(property: 'city', description: 'City', type: 'string', example: 'New York'),
                    'state' => new OA\Property(property: 'state', description: 'State/Province', type: 'string', example: 'NY'),
                    'country' => new OA\Property(property: 'country', description: 'Country', type: 'string', example: 'USA'),
                    'postal_code' => new OA\Property(property: 'postal_code', description: 'Postal code', type: 'string', example: '10001'),
                    'latitude' => new OA\Property(property: 'latitude', description: 'Latitude', type: 'number', format: 'float', example: 40.7128),
                    'longitude' => new OA\Property(property: 'longitude', description: 'Longitude', type: 'number', format: 'float', example: -74.0060),
                    'is_headquarters' => new OA\Property(property: 'is_headquarters', description: 'Is headquarters', type: 'boolean', example: true),
                    'is_active' => new OA\Property(property: 'is_active', description: 'Active status', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Locations'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Location created successfully',
                content: new OA\JsonContent(
                    properties: [
                        'id' => new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        'name' => new OA\Property(property: 'name', type: 'string'),
                        'city' => new OA\Property(property: 'city', type: 'string'),
                        'country' => new OA\Property(property: 'country', type: 'string'),
                    ],
                    type: 'object'
                )
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
        description: 'Get details of a specific location',
        summary: 'Get location details',
        security: [['bearerAuth' => []]],
        tags: ['Locations'],
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
                content: new OA\JsonContent(
                    properties: [
                        'id' => new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        'name' => new OA\Property(property: 'name', type: 'string'),
                        'city' => new OA\Property(property: 'city', type: 'string'),
                        'country' => new OA\Property(property: 'country', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Location not found'),
        ]
    )]
    public function show(Request $request, Location $location): JsonResponse
    {
        $includes = $request->get('include', '');
        $allowedIncludes = ['customer', 'assets'];
        $includes = array_intersect(explode(',', $includes), $allowedIncludes);

        if (! empty($includes)) {
            $location->load($includes);
        }

        return response()->json($location);
    }

    #[OA\Put(
        path: '/api/v1/locations/{id}',
        description: 'Update an existing location',
        summary: 'Update location',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    'name' => new OA\Property(property: 'name', description: 'Location name', type: 'string', example: 'New York Office'),
                    'customer_id' => new OA\Property(property: 'customer_id', description: 'Customer ID', type: 'string', format: 'uuid'),
                    'code' => new OA\Property(property: 'code', description: 'Location code', type: 'string', example: 'NYC'),
                    'description' => new OA\Property(property: 'description', description: 'Location description', type: 'string'),
                    'address' => new OA\Property(property: 'address', description: 'Street address', type: 'string'),
                    'city' => new OA\Property(property: 'city', description: 'City', type: 'string'),
                    'state' => new OA\Property(property: 'state', description: 'State/Province', type: 'string'),
                    'country' => new OA\Property(property: 'country', description: 'Country', type: 'string'),
                    'postal_code' => new OA\Property(property: 'postal_code', description: 'Postal code', type: 'string'),
                    'latitude' => new OA\Property(property: 'latitude', description: 'Latitude', type: 'number', format: 'float'),
                    'longitude' => new OA\Property(property: 'longitude', description: 'Longitude', type: 'number', format: 'float'),
                    'is_headquarters' => new OA\Property(property: 'is_headquarters', description: 'Is headquarters', type: 'boolean'),
                    'is_active' => new OA\Property(property: 'is_active', description: 'Active status', type: 'boolean'),
                ],
                type: 'object'
            )
        ),
        tags: ['Locations'],
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
                description: 'Location updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        'id' => new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        'name' => new OA\Property(property: 'name', type: 'string'),
                        'city' => new OA\Property(property: 'city', type: 'string'),
                        'country' => new OA\Property(property: 'country', type: 'string'),
                    ],
                    type: 'object'
                )
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
        description: 'Delete a location (soft delete)',
        summary: 'Delete location',
        security: [['bearerAuth' => []]],
        tags: ['Locations'],
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
        description: 'Get all assets at a specific location',
        summary: 'Get location assets',
        security: [['bearerAuth' => []]],
        tags: ['Locations'],
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
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    'id' => new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    'name' => new OA\Property(property: 'name', type: 'string'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
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
