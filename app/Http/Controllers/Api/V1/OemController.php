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
        summary: 'List all OEMs',
        description: 'Get a paginated list of all OEMs with optional filtering',
        tags: ['OEMs'],
        security: [['bearerAuth' => []]],
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
                schema: new OA\Schema(type: 'string', enum: ['name', 'created_at', '-name', '-created_at'])
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
                            items: new OA\Items(ref: '#/components/schemas/Oem')
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
        $oems = QueryBuilder::for(Oem::class)
            ->allowedFilters(['name', 'is_active'])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($oems);
    }

    #[OA\Post(
        path: '/api/v1/oems',
        summary: 'Create a new OEM',
        description: 'Create a new Original Equipment Manufacturer',
        tags: ['OEMs'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    'name' => new OA\Property(type: 'string', description: 'OEM name', example: 'Dell Technologies'),
                    'description' => new OA\Property(type: 'string', description: 'OEM description', example: 'Computer technology company'),
                    'website' => new OA\Property(type: 'string', format: 'url', description: 'OEM website', example: 'https://www.dell.com'),
                    'contact_email' => new OA\Property(type: 'string', format: 'email', description: 'Contact email', example: 'support@dell.com'),
                    'is_active' => new OA\Property(type: 'boolean', description: 'Active status', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'OEM created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Oem')
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:oems,name',
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $oem = Oem::create($validated);

        return response()->json($oem, 201);
    }

    #[OA\Get(
        path: '/api/v1/oems/{id}',
        summary: 'Get OEM details',
        description: 'Get details of a specific OEM',
        tags: ['OEMs'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'OEM ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/Oem')
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
        summary: 'Update OEM',
        description: 'Update an existing OEM',
        tags: ['OEMs'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'OEM ID',
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
                    'name' => new OA\Property(type: 'string', description: 'OEM name', example: 'Dell Technologies'),
                    'description' => new OA\Property(type: 'string', description: 'OEM description', example: 'Computer technology company'),
                    'website' => new OA\Property(type: 'string', format: 'url', description: 'OEM website', example: 'https://www.dell.com'),
                    'contact_email' => new OA\Property(type: 'string', format: 'email', description: 'Contact email', example: 'support@dell.com'),
                    'is_active' => new OA\Property(type: 'boolean', description: 'Active status', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OEM updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Oem')
            ),
            new OA\Response(response: 404, description: 'OEM not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Oem $oem): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:oems,name,' . $oem->id,
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $oem->update($validated);

        return response()->json($oem);
    }

    #[OA\Delete(
        path: '/api/v1/oems/{id}',
        summary: 'Delete OEM',
        description: 'Delete an OEM (soft delete)',
        tags: ['OEMs'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'OEM ID',
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