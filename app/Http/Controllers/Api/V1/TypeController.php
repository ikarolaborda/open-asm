<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Shared\Models\Type;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Types', description: 'Asset type management')]
class TypeController extends Controller
{
    #[OA\Get(
        path: '/api/v1/types',
        description: 'Get a paginated list of all asset types with optional filtering and inclusion of related assets',
        summary: 'List all asset types',
        security: [['bearerAuth' => []]],
        tags: ['Types'],
        parameters: [
            new OA\Parameter(
                name: 'filter[name]',
                description: 'Filter by type name',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[category]',
                description: 'Filter by category (hardware, software, service)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['hardware', 'software', 'service'])
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
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['name', 'category', 'created_at', '-name', '-category', '-created_at']
                )
            ),
            new OA\Parameter(
                name: 'include',
                description: 'Include related resources (comma-separated: assets)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        // array of types
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id',          type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'name',        type: 'string'),
                                    new OA\Property(property: 'code',        type: 'string', nullable: true),
                                    new OA\Property(property: 'description', type: 'string', nullable: true),
                                    new OA\Property(property: 'category', type: 'string', enum: ['hardware','software','service'], nullable: true),
                                    new OA\Property(property: 'is_active',   type: 'boolean'),
                                    new OA\Property(property: 'created_at',  type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at',  type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                        // pagination meta (existing schema)
                        new OA\Property(
                            property: 'meta',
                            ref: '#/components/schemas/PaginationMeta'
                        ),
                        // inline pagination links
                        new OA\Property(
                            property: 'links',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'url',    type: 'string', nullable: true),
                                    new OA\Property(property: 'label',  type: 'string'),
                                    new OA\Property(property: 'active', type: 'boolean'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $types = QueryBuilder::for(Type::class)
            ->allowedFilters(['name', 'category', 'is_active', 'code'])
            ->allowedSorts(['name', 'category', 'created_at'])
            ->allowedIncludes(['assets'])
            ->defaultSort('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($types);
    }

    #[OA\Post(
        path: '/api/v1/types',
        description: 'Create a new asset type for categorization',
        summary: 'Create a new asset type',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', description: 'Type name', type: 'string', example: 'Laptop'),
                    new OA\Property(property: 'code', description: 'Type code', type: 'string', example: 'LAP'),
                    new OA\Property(property: 'description', description: 'Type description', type: 'string', example: 'Portable computing devices'),
                    new OA\Property(property: 'category', description: 'Type category', type: 'string', enum: ['hardware','software','service'], example: 'hardware'),
                    new OA\Property(property: 'is_active', description: 'Active status', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Types'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Type created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id',          type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name',        type: 'string'),
                        new OA\Property(property: 'code',        type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'category', type: 'string', enum: ['hardware','software','service'], nullable: true),
                        new OA\Property(property: 'is_active',   type: 'boolean'),
                        new OA\Property(property: 'created_at',  type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at',  type: 'string', format: 'date-time'),
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
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50|unique:types,code,NULL,id,organization_id,' . auth()->user()->organization_id,
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|string|in:hardware,software,service',
            'is_active'   => 'boolean',
        ]);

        $type = Type::create($validated);

        return response()->json($type, 201);
    }

    #[OA\Get(
        path: '/api/v1/types/{id}',
        description: 'Get details of a specific asset type',
        summary: 'Get type details',
        security: [['bearerAuth' => []]],
        tags: ['Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Type ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'include',
                description: 'Include related resources (assets)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id',          type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name',        type: 'string'),
                        new OA\Property(property: 'code',        type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'category', type: 'string', enum: ['hardware','software','service'], nullable: true),
                        new OA\Property(property: 'is_active',   type: 'boolean'),
                        new OA\Property(property: 'created_at',  type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at',  type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Type not found'),
        ]
    )]
    public function show(Request $request, Type $type): JsonResponse
    {
        $includes = array_intersect(
            explode(',', $request->get('include', '')),
            ['assets']
        );

        if (!empty($includes)) {
            $type->load($includes);
        }

        return response()->json($type);
    }

    #[OA\Put(
        path: '/api/v1/types/{id}',
        description: 'Update an existing asset type',
        summary: 'Update asset type',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', description: 'Type name', type: 'string', example: 'Laptop'),
                    new OA\Property(property: 'code', description: 'Type code', type: 'string', example: 'LAP'),
                    new OA\Property(property: 'description', description: 'Type description', type: 'string', example: 'Portable computing devices'),
                    new OA\Property(property: 'category', description: 'Type category', type: 'string', enum: ['hardware','software','service'], example: 'hardware'),
                    new OA\Property(property: 'is_active', description: 'Active status', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Type ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Type updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id',          type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name',        type: 'string'),
                        new OA\Property(property: 'code',        type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'category', type: 'string', enum: ['hardware','software','service'], nullable: true),
                        new OA\Property(property: 'is_active',   type: 'boolean'),
                        new OA\Property(property: 'created_at',  type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at',  type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Type not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Type $type): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50|unique:types,code,' . $type->id . ',id,organization_id,' . auth()->user()->organization_id,
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|string|in:hardware,software,service',
            'is_active'   => 'boolean',
        ]);

        $type->update($validated);

        return response()->json($type);
    }

    #[OA\Delete(
        path: '/api/v1/types/{id}',
        description: 'Delete an asset type (soft delete)',
        summary: 'Delete asset type',
        security: [['bearerAuth' => []]],
        tags: ['Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Type ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Type deleted successfully'),
            new OA\Response(response: 404, description: 'Type not found'),
        ]
    )]
    public function destroy(Type $type): JsonResponse
    {
        $type->delete();

        return response()->json(null, 204);
    }
}
