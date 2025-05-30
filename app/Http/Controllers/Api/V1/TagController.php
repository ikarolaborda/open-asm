<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Shared\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tags', description: 'Tag management for flexible categorization')]
class TagController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tags',
        description: 'Get a paginated list of all tags with optional filtering and inclusion of related assets',
        summary: 'List all tags',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(
                name: 'filter[name]',
                description: 'Filter by tag name',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[color]',
                description: 'Filter by tag color',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
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
                    enum: ['name', 'created_at', '-name', '-created_at']
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
                        // tag list
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'color', type: 'string', nullable: true),
                                    new OA\Property(property: 'description', type: 'string', nullable: true),
                                    new OA\Property(property: 'is_active', type: 'boolean'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                        // pagination meta
                        new OA\Property(
                            property: 'meta',
                            ref: '#/components/schemas/PaginationMeta'
                        ),
                        // pagination links
                        new OA\Property(
                            property: 'links',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'url', type: 'string', nullable: true),
                                    new OA\Property(property: 'label', type: 'string'),
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
        $tags = QueryBuilder::for(Tag::class)
            ->allowedFilters(['name', 'color', 'is_active'])
            ->allowedSorts(['name', 'created_at'])
            ->allowedIncludes(['assets'])
            ->defaultSort('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($tags);
    }

    #[OA\Post(
        path: '/api/v1/tags',
        description: 'Create a new tag for flexible categorization',
        summary: 'Create a new tag',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', description: 'Tag name', type: 'string', example: 'Critical'),
                    new OA\Property(property: 'color', description: 'Tag color (hex)', type: 'string', example: '#FF0000'),
                    new OA\Property(property: 'description', description: 'Tag description', type: 'string', example: 'Requires immediate attention'),
                    new OA\Property(property: 'is_active', description: 'Active status', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Tags'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tag created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'color', type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
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
            'name'        => 'required|string|max:255|unique:tags,name,NULL,id,organization_id,' . auth()->user()->organization_id,
            'color'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ]);

        $tag = Tag::create($validated);

        return response()->json($tag, 201);
    }

    #[OA\Get(
        path: '/api/v1/tags/{id}',
        description: 'Get details of a specific tag',
        summary: 'Get tag details',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Tag ID',
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
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'color', type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Tag not found'),
        ]
    )]
    public function show(Request $request, Tag $tag): JsonResponse
    {
        $includes = array_intersect(
            explode(',', $request->get('include', '')),
            ['assets']
        );

        if (! empty($includes)) {
            $tag->load($includes);
        }

        return response()->json($tag);
    }

    #[OA\Put(
        path: '/api/v1/tags/{id}',
        description: 'Update an existing tag',
        summary: 'Update tag',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', description: 'Tag name', type: 'string', example: 'Critical'),
                    new OA\Property(property: 'color', description: 'Tag color (hex)', type: 'string', example: '#FF0000'),
                    new OA\Property(property: 'description', description: 'Tag description', type: 'string', example: 'Requires immediate attention'),
                    new OA\Property(property: 'is_active', description: 'Active status', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Tag ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tag updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'color', type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Tag not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:tags,name,' . $tag->id . ',id,organization_id,' . auth()->user()->organization_id,
            'color'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ]);

        $tag->update($validated);

        return response()->json($tag);
    }

    #[OA\Delete(
        path: '/api/v1/tags/{id}',
        description: 'Delete a tag (soft delete)',
        summary: 'Delete tag',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Tag ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Tag deleted successfully'),
            new OA\Response(response: 404, description: 'Tag not found'),
        ]
    )]
    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/api/v1/tags/{id}/assets',
        description: 'Attach a tag to multiple assets',
        summary: 'Attach tag to assets',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['asset_ids'],
                properties: [
                    new OA\Property(
                        property: 'asset_ids',
                        description: 'Array of asset IDs',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'uuid'),
                        example: ['550e8400-e29b-41d4-a716-446655440000', '550e8400-e29b-41d4-a716-446655440001']
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Tag ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tag attached to assets successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Tag attached to 2 assets'),
                        new OA\Property(property: 'attached_count', type: 'integer', example: 2),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Tag not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function attachToAssets(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'asset_ids'   => 'required|array',
            'asset_ids.*' => 'uuid|exists:assets,id',
        ]);

        $tag->assets()->syncWithoutDetaching($validated['asset_ids']);
        $attachedCount = count($validated['asset_ids']);

        return response()->json([
            'message'        => "Tag attached to {$attachedCount} assets",
            'attached_count' => $attachedCount,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tags/{id}/assets',
        description: 'Detach a tag from multiple assets',
        summary: 'Detach tag from assets',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['asset_ids'],
                properties: [
                    new OA\Property(
                        property: 'asset_ids',
                        description: 'Array of asset IDs',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'uuid'),
                        example: ['550e8400-e29b-41d4-a716-446655440000', '550e8400-e29b-41d4-a716-446655440001']
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Tag ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tag detached from assets successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Tag detached from 2 assets'),
                        new OA\Property(property: 'detached_count', type: 'integer', example: 2),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Tag not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function detachFromAssets(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'asset_ids'   => 'required|array',
            'asset_ids.*' => 'uuid|exists:assets,id',
        ]);

        $tag->assets()->detach($validated['asset_ids']);
        $detachedCount = count($validated['asset_ids']);

        return response()->json([
            'message'        => "Tag detached from {$detachedCount} assets",
            'detached_count' => $detachedCount,
        ]);
    }
}
