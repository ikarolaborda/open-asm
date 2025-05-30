<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Shared\Models\Oem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'OEMs', description: 'Original Equipment Manufacturer management')]
class OemController extends Controller
{
    #[OA\Get(
        path: '/api/v1/oems',
        description: 'Get a paginated list of all OEMs with optional filtering',
        summary: 'List all OEMs',
        security: [['bearerAuth' => []]],
        tags: ['OEMs'],
        parameters: [
            new OA\Parameter(
                name: 'filter[name]',
                description: 'Filter by OEM name',
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
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
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
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true),
                                    new OA\Property(property: 'website', type: 'string', format: 'url', nullable: true),
                                    new OA\Property(property: 'contact_email', type: 'string', format: 'email', nullable: true),
                                    new OA\Property(property: 'is_active', type: 'boolean'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            ref: '#/components/schemas/PaginationMeta'
                        ),
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
        $oems = QueryBuilder::for(Oem::class)
            ->allowedFilters(['name', 'is_active'])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($oems);
    }

    #[OA\Post(
        path: '/api/v1/oems',
        description: 'Create a new Original Equipment Manufacturer',
        summary: 'Create a new OEM',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        description: 'OEM name',
                        type: 'string',
                        example: 'Dell Technologies'
                    ),
                    new OA\Property(
                        property: 'description',
                        description: 'OEM description',
                        type: 'string',
                        example: 'Computer technology company'
                    ),
                    new OA\Property(
                        property: 'website',
                        description: 'OEM website',
                        type: 'string',
                        format: 'url',
                        example: 'https://www.dell.com'
                    ),
                    new OA\Property(
                        property: 'contact_email',
                        description: 'Contact email',
                        type: 'string',
                        format: 'email',
                        example: 'support@dell.com'
                    ),
                    new OA\Property(
                        property: 'is_active',
                        description: 'Active status',
                        type: 'boolean',
                        example: true
                    ),
                ]
            )
        ),
        tags: ['OEMs'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'OEM created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'website', type: 'string', format: 'url', nullable: true),
                        new OA\Property(property: 'contact_email', type: 'string', format: 'email', nullable: true),
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
            'name'          => 'required|string|max:255|unique:oems,name',
            'description'   => 'nullable|string|max:1000',
            'website'       => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'is_active'     => 'boolean',
        ]);

        $oem = Oem::create($validated);

        return response()->json($oem, 201);
    }

    #[OA\Get(
        path: '/api/v1/oems/{id}',
        description: 'Get details of a specific OEM',
        summary: 'Get OEM details',
        security: [['bearerAuth' => []]],
        tags: ['OEMs'],
        parameters: [
            new OA\Parameter(
                name: 'id',
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
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'website', type: 'string', format: 'url', nullable: true),
                        new OA\Property(property: 'contact_email', type: 'string', format: 'email', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'OEM not found'),
        ]
    )]
    public function show(Oem $oem): JsonResponse
    {
        return response()->json($oem);
    }

    #[OA\Put(
        path: '/api/v1/oems/{id}',
        description: 'Update an existing OEM',
        summary: 'Update OEM',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        description: 'OEM name',
                        type: 'string',
                        example: 'Dell Technologies'
                    ),
                    new OA\Property(
                        property: 'description',
                        description: 'OEM description',
                        type: 'string',
                        example: 'Computer technology company'
                    ),
                    new OA\Property(
                        property: 'website',
                        description: 'OEM website',
                        type: 'string',
                        format: 'url',
                        example: 'https://www.dell.com'
                    ),
                    new OA\Property(
                        property: 'contact_email',
                        description: 'Contact email',
                        type: 'string',
                        format: 'email',
                        example: 'support@dell.com'
                    ),
                    new OA\Property(
                        property: 'is_active',
                        description: 'Active status',
                        type: 'boolean',
                        example: true
                    ),
                ]
            )
        ),
        tags: ['OEMs'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OEM updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'website', type: 'string', format: 'url', nullable: true),
                        new OA\Property(property: 'contact_email', type: 'string', format: 'email', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'OEM not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Oem $oem): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:oems,name,' . $oem->id,
            'description'   => 'nullable|string|max:1000',
            'website'       => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'is_active'     => 'boolean',
        ]);

        $oem->update($validated);

        return response()->json($oem);
    }

    #[OA\Delete(
        path: '/api/v1/oems/{id}',
        description: 'Delete an OEM (soft delete)',
        summary: 'Delete OEM',
        security: [['bearerAuth' => []]],
        tags: ['OEMs'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'OEM deleted successfully'),
            new OA\Response(response: 404, description: 'OEM not found'),
        ]
    )]
    public function destroy(Oem $oem): JsonResponse
    {
        $oem->delete();

        return response()->json(null, 204);
    }
}
