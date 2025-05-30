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
        summary: 'List all products',
        description: 'Get a paginated list of all products with optional filtering',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
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
                schema: new OA\Schema(type: 'string', enum: ['name', 'model_number', 'created_at', '-name', '-model_number', '-created_at'])
            ),
            new OA\Parameter(
                name: 'include',
                description: 'Include related resources',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['oem', 'productLine', 'assets'])
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
                            items: new OA\Items(ref: '#/components/schemas/Product')
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
        summary: 'Create a new product',
        description: 'Create a new product in the catalog',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    'name' => new OA\Property(type: 'string', description: 'Product name', example: 'Dell PowerEdge R750'),
                    'oem_id' => new OA\Property(type: 'string', format: 'uuid', description: 'OEM ID', example: '550e8400-e29b-41d4-a716-446655440000'),
                    'product_line_id' => new OA\Property(type: 'string', format: 'uuid', description: 'Product line ID', example: '550e8400-e29b-41d4-a716-446655440001'),
                    'model_number' => new OA\Property(type: 'string', description: 'Model number', example: 'R750'),
                    'part_number' => new OA\Property(type: 'string', description: 'Part number', example: 'PER750-001'),
                    'description' => new OA\Property(type: 'string', description: 'Product description', example: 'High-performance rack server'),
                    'is_active' => new OA\Property(type: 'boolean', description: 'Active status', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Product')
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'oem_id' => 'nullable|uuid|exists:oems,id',
            'product_line_id' => 'nullable|uuid|exists:product_lines,id',
            'model_number' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json($product->load(['oem', 'productLine']), 201);
    }

    #[OA\Get(
        path: '/api/v1/products/{id}',
        summary: 'Get product details',
        description: 'Get details of a specific product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
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
                description: 'Include related resources',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['oem', 'productLine', 'assets'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/Product')
            ),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(Request $request, Product $product): JsonResponse
    {
        $includes = $request->get('include', '');
        $allowedIncludes = ['oem', 'productLine', 'assets'];
        $includes = array_intersect(explode(',', $includes), $allowedIncludes);

        if (!empty($includes)) {
            $product->load($includes);
        }

        return response()->json($product);
    }

    #[OA\Put(
        path: '/api/v1/products/{id}',
        summary: 'Update product',
        description: 'Update an existing product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Product ID',
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
                    'name' => new OA\Property(type: 'string', description: 'Product name', example: 'Dell PowerEdge R750'),
                    'oem_id' => new OA\Property(type: 'string', format: 'uuid', description: 'OEM ID', example: '550e8400-e29b-41d4-a716-446655440000'),
                    'product_line_id' => new OA\Property(type: 'string', format: 'uuid', description: 'Product line ID', example: '550e8400-e29b-41d4-a716-446655440001'),
                    'model_number' => new OA\Property(type: 'string', description: 'Model number', example: 'R750'),
                    'part_number' => new OA\Property(type: 'string', description: 'Part number', example: 'PER750-001'),
                    'description' => new OA\Property(type: 'string', description: 'Product description', example: 'High-performance rack server'),
                    'is_active' => new OA\Property(type: 'boolean', description: 'Active status', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Product')
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'oem_id' => 'nullable|uuid|exists:oems,id',
            'product_line_id' => 'nullable|uuid|exists:product_lines,id',
            'model_number' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json($product->load(['oem', 'productLine']));
    }

    #[OA\Delete(
        path: '/api/v1/products/{id}',
        summary: 'Delete product',
        description: 'Delete a product (soft delete)',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
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