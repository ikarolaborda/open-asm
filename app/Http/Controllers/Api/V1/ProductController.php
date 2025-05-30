<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Shared\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Products', description: 'Product catalog management')]
class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/v1/products',
        description: 'Get a paginated list of all products with optional filtering and inclusion of related resources',
        summary: 'List all products',
        security: [['bearerAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'filter[name]',
                description: 'Filter by product name',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[oem_id]',
                description: 'Filter by OEM ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'filter[product_line_id]',
                description: 'Filter by product line ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
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
                    enum: ['name', 'model_number', 'created_at', '-name', '-model_number', '-created_at']
                )
            ),
            new OA\Parameter(
                name: 'include',
                description: 'Include related resources (comma-separated: oem,productLine,assets)',
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
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id',              type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'name',            type: 'string'),
                                    new OA\Property(property: 'oem_id',          type: 'string', format: 'uuid', nullable: true),
                                    new OA\Property(property: 'product_line_id', type: 'string', format: 'uuid', nullable: true),
                                    new OA\Property(property: 'model_number',    type: 'string', nullable: true),
                                    new OA\Property(property: 'part_number',     type: 'string', nullable: true),
                                    new OA\Property(property: 'description',     type: 'string', nullable: true),
                                    new OA\Property(property: 'is_active',       type: 'boolean'),
                                    new OA\Property(property: 'created_at',      type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at',      type: 'string', format: 'date-time'),
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
        $products = QueryBuilder::for(Product::class)
            ->allowedFilters(['name', 'oem_id', 'product_line_id', 'is_active', 'model_number', 'part_number'])
            ->allowedSorts(['name', 'model_number', 'created_at'])
            ->allowedIncludes(['oem', 'productLine', 'assets'])
            ->defaultSort('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    #[OA\Post(
        path: '/api/v1/products',
        description: 'Create a new product in the catalog',
        summary: 'Create a new product',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', description: 'Product name', type: 'string', example: 'Dell PowerEdge R750'),
                    new OA\Property(property: 'oem_id', description: 'OEM ID', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                    new OA\Property(property: 'product_line_id', description: 'Product line ID', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440001'),
                    new OA\Property(property: 'model_number', description: 'Model number', type: 'string', example: 'R750'),
                    new OA\Property(property: 'part_number', description: 'Part number', type: 'string', example: 'PER750-001'),
                    new OA\Property(property: 'description', description: 'Product description', type: 'string', example: 'High-performance rack server'),
                    new OA\Property(property: 'is_active', description: 'Active status', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id',              type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name',            type: 'string'),
                        new OA\Property(property: 'oem_id',          type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'product_line_id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'model_number',    type: 'string', nullable: true),
                        new OA\Property(property: 'part_number',     type: 'string', nullable: true),
                        new OA\Property(property: 'description',     type: 'string', nullable: true),
                        new OA\Property(property: 'is_active',       type: 'boolean'),
                        new OA\Property(property: 'created_at',      type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at',      type: 'string', format: 'date-time'),
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
            'name'             => 'required|string|max:255',
            'oem_id'           => 'nullable|uuid|exists:oems,id',
            'product_line_id'  => 'nullable|uuid|exists:product_lines,id',
            'model_number'     => 'nullable|string|max:255',
            'part_number'      => 'nullable|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'is_active'        => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json($product->load(['oem', 'productLine']), 201);
    }

    #[OA\Get(
        path: '/api/v1/products/{id}',
        description: 'Get details of a specific product',
        summary: 'Get product details',
        security: [['bearerAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'include',
                description: 'Include related resources (comma-separated: oem,productLine,assets)',
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
                        new OA\Property(property: 'id',              type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name',            type: 'string'),
                        new OA\Property(property: 'oem_id',          type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'product_line_id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'model_number',    type: 'string', nullable: true),
                        new OA\Property(property: 'part_number',     type: 'string', nullable: true),
                        new OA\Property(property: 'description',     type: 'string', nullable: true),
                        new OA\Property(property: 'is_active',       type: 'boolean'),
                        new OA\Property(property: 'created_at',      type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at',      type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(Request $request, Product $product): JsonResponse
    {
        $includes = array_intersect(
            explode(',', $request->get('include', '')),
            ['oem', 'productLine', 'assets']
        );

        if (!empty($includes)) {
            $product->load($includes);
        }

        return response()->json($product);
    }

    #[OA\Put(
        path: '/api/v1/products/{id}',
        description: 'Update an existing product',
        summary: 'Update product',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',            type: 'string', example: 'Dell PowerEdge R750'),
                    new OA\Property(property: 'oem_id',          type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                    new OA\Property(property: 'product_line_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440001'),
                    new OA\Property(property: 'model_number',    type: 'string', example: 'R750'),
                    new OA\Property(property: 'part_number',     type: 'string', example: 'PER750-001'),
                    new OA\Property(property: 'description',     type: 'string', example: 'High-performance rack server'),
                    new OA\Property(property: 'is_active',       type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id',              type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name',            type: 'string'),
                        new OA\Property(property: 'oem_id',          type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'product_line_id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'model_number',    type: 'string', nullable: true),
                        new OA\Property(property: 'part_number',     type: 'string', nullable: true),
                        new OA\Property(property: 'description',     type: 'string', nullable: true),
                        new OA\Property(property: 'is_active',       type: 'boolean'),
                        new OA\Property(property: 'created_at',      type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at',      type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'oem_id'           => 'nullable|uuid|exists:oems,id',
            'product_line_id'  => 'nullable|uuid|exists:product_lines,id',
            'model_number'     => 'nullable|string|max:255',
            'part_number'      => 'nullable|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'is_active'        => 'boolean',
        ]);

        $product->update($validated);

        return response()->json($product->load(['oem', 'productLine']));
    }

    #[OA\Delete(
        path: '/api/v1/products/{id}',
        description: 'Delete a product (soft delete)',
        summary: 'Delete product',
        security: [['bearerAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Product deleted successfully'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
