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
        summary: 'List all asset types',
        description: 'Get a paginated list of all asset types with optional filtering',
        tags: ['Types'],
        security: [['bearerAuth' => []]],
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
                schema: new OA\Schema(type: 'string', enum: ['name', 'category', 'created_at', '-name', '-category', '-created_at'])
            ),
            new OA\Parameter(
                name: 'include',
                description: 'Include related resources',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['assets'])
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
                            items: new OA\Items(ref: '#/components/schemas/Type')
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
        summary: 'Create a new asset type',
        description: 'Create a new asset type for categorization',
        tags: ['Types'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    'name' => new OA\Property(type: 'string', description: 'Type name', example: 'Laptop'),
                    'code' => new OA\Property(type: 'string', description: 'Type code', example: 'LAP'),
                    'description' => new OA\Property(type: 'string', description: 'Type description', example: 'Portable computing devices'),
                    'category' => new OA\Property(type: 'string', enum: ['hardware', 'software', 'service'], description: 'Type category', example: 'hardware'),
                    'is_active' => new OA\Property(type: 'boolean', description: 'Active status', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Type created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Type')
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:types,code,NULL,id,organization_id,' . auth()->user()->organization_id,
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|in:hardware,software,service',
            'is_active' => 'boolean',
        ]);

        $type = Type::create($validated);

        return response()->json($type, 201);
    }

    #[OA\Get(
        path: '/api/v1/types/{id}',
        summary: 'Get type details',
        description: 'Get details of a specific asset type',
        tags: ['Types'],
        security: [['bearerAuth' => []]],
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
                description: 'Include related resources',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['assets'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/Type')
            ),
            new OA\Response(response: 404, description: 'Type not found'),
        ]
    )]
    public function show(Request $request, Type $type): JsonResponse
    {
        $includes = $request->get('include', '');
        $allowedIncludes = ['assets'];
        $includes = array_intersect(explode(',', $includes), $allowedIncludes);

        if (!empty($includes)) {
            $type->load($includes);
        }

        return response()->json($type);
    }

    #[OA\Put(
        path: '/api/v1/types/{id}',
        summary: 'Update asset type',
        description: 'Update an existing asset type',
        tags: ['Types'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Type ID',
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
                    'name' => new OA\Property(type: 'string', description: 'Type name', example: 'Laptop'),
                    'code' => new OA\Property(type: 'string', description: 'Type code', example: 'LAP'),
                    'description' => new OA\Property(type: 'string', description: 'Type description', example: 'Portable computing devices'),
                    'category' => new OA\Property(type: 'string', enum: ['hardware', 'software', 'service'], description: 'Type category', example: 'hardware'),
                    'is_active' => new OA\Property(type: 'boolean', description: 'Active status', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Type updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Type')
            ),
            new OA\Response(response: 404, description: 'Type not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Type $type): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:types,code,' . $type->id . ',id,organization_id,' . auth()->user()->organization_id,
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|in:hardware,software,service',
            'is_active' => 'boolean',
        ]);

        $type->update($validated);

        return response()->json($type);
    }

    #[OA\Delete(
        path: '/api/v1/types/{id}',
        summary: 'Delete asset type',
        description: 'Delete an asset type (soft delete)',
        tags: ['Types'],
        security: [['bearerAuth' => []]],
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