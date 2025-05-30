<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Services\AssetService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\CreateAssetRequest;
use App\Http\Requests\Asset\UpdateAssetRequest;
use App\Http\Resources\Asset\AssetCollection;
use App\Http\Resources\Asset\AssetResource;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Assets", description: "Asset management endpoints")]
class AssetController extends Controller
{
    public function __construct(
        private AssetService $assetService,
        private LoggingService $loggingService
    ) {}

    #[OA\Get(
        path: "/api/v1/assets",
        summary: "Get paginated list of assets",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, default: 15)
            ),
            new OA\Parameter(
                name: "sort",
                in: "query",
                description: "Sort field (prefix with - for descending)",
                required: false,
                schema: new OA\Schema(type: "string", default: "name")
            ),
            new OA\Parameter(
                name: "filter[is_active]",
                in: "query",
                description: "Filter by active status",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),
            new OA\Parameter(
                name: "filter[customer_id]",
                in: "query",
                description: "Filter by customer ID",
                required: false,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent(ref: "#/components/schemas/AssetCollection")
            )
        ]
    )]
    public function index(Request $request): AssetCollection
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        $assets = $this->assetService->paginate(
            perPage: (int) $request->get('per_page', 15),
            filters: $request->get('filter', []),
            sort: $request->get('sort', 'name'),
            includes: $request->get('include', [])
        );

        $this->loggingService->logBusinessOperation('list', 'asset', 'multiple');

        return new AssetCollection($assets);
    }

    #[OA\Post(
        path: "/api/v1/assets",
        summary: "Create a new asset",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateAssetRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Asset created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/AssetResource")
                    ]
                )
            )
        ]
    )]
    public function store(CreateAssetRequest $request): JsonResponse
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        try {
            $asset = $this->assetService->create($request->validated());

            $this->loggingService->logBusinessOperation('create', 'asset', $asset->id);

            return response()->json([
                'message' => 'Asset created successfully.',
                'data' => new AssetResource($asset->load(['organization', 'customer', 'location', 'type', 'warranties'])),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to create asset', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create asset.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/api/v1/assets/{asset}",
        summary: "Get a specific asset",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "asset",
                in: "path",
                description: "Asset ID",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent(ref: "#/components/schemas/AssetResource")
            )
        ]
    )]
    public function show(Request $request, Asset $asset): AssetResource
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        $includes = $request->get('include', []);
        if (! empty($includes)) {
            $asset->load($includes);
        }

        $this->loggingService->logBusinessOperation('view', 'asset', $asset->id);

        return new AssetResource($asset);
    }

    #[OA\Put(
        path: "/api/v1/assets/{asset}",
        summary: "Update an asset",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "asset",
                in: "path",
                description: "Asset ID",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateAssetRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Asset updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/AssetResource")
                    ]
                )
            )
        ]
    )]
    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        try {
            $updatedAsset = $this->assetService->update($asset, $request->validated());

            $this->loggingService->logBusinessOperation('update', 'asset', $asset->id);

            return response()->json([
                'message' => 'Asset updated successfully.',
                'data' => new AssetResource($updatedAsset->load(['organization', 'customer', 'location', 'type', 'warranties'])),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to update asset', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update asset.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: "/api/v1/assets/{asset}",
        summary: "Delete an asset (soft delete)",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "asset",
                in: "path",
                description: "Asset ID",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asset deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            )
        ]
    )]
    public function destroy(Asset $asset): JsonResponse
    {
        try {
            $this->assetService->delete($asset);

            $this->loggingService->logBusinessOperation('delete', 'asset', $asset->id);

            return response()->json([
                'message' => 'Asset deleted successfully.',
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to delete asset', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to delete asset.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: "/api/v1/assets/{id}/restore",
        summary: "Restore a soft-deleted asset",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Asset ID",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asset restored successfully"
            )
        ]
    )]
    public function restore(string $id): JsonResponse
    {
        try {
            $asset = $this->assetService->restore($id);

            $this->loggingService->logBusinessOperation('restore', 'asset', $id);

            return response()->json([
                'message' => 'Asset restored successfully.',
                'data' => new AssetResource($asset),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to restore asset', [
                'asset_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to restore asset.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: "/api/v1/assets/{id}/force",
        summary: "Permanently delete an asset",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Asset ID",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asset permanently deleted"
            )
        ]
    )]
    public function forceDestroy(string $id): JsonResponse
    {
        try {
            $this->assetService->forceDelete($id);

            $this->loggingService->logBusinessOperation('force_delete', 'asset', $id);

            return response()->json([
                'message' => 'Asset permanently deleted.',
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to force delete asset', [
                'asset_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to permanently delete asset.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Patch(
        path: "/api/v1/assets/{asset}/retire",
        summary: "Retire an asset",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "asset",
                in: "path",
                description: "Asset ID",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asset retired successfully"
            )
        ]
    )]
    public function retire(Asset $asset): JsonResponse
    {
        try {
            $this->assetService->retire($asset);

            $this->loggingService->logBusinessOperation('retire', 'asset', $asset->id);

            return response()->json([
                'message' => 'Asset retired successfully.',
                'data' => new AssetResource($asset->fresh()),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to retire asset', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retire asset.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Patch(
        path: "/api/v1/assets/{asset}/reactivate",
        summary: "Reactivate a retired asset",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "asset",
                in: "path",
                description: "Asset ID",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asset reactivated successfully"
            )
        ]
    )]
    public function reactivate(Asset $asset): JsonResponse
    {
        try {
            $this->assetService->reactivate($asset);

            $this->loggingService->logBusinessOperation('reactivate', 'asset', $asset->id);

            return response()->json([
                'message' => 'Asset reactivated successfully.',
                'data' => new AssetResource($asset->fresh()),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to reactivate asset', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to reactivate asset.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Patch(
        path: "/api/v1/assets/{asset}/calculate-quality",
        summary: "Calculate and update data quality score",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "asset",
                in: "path",
                description: "Asset ID",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data quality calculated successfully"
            )
        ]
    )]
    public function calculateDataQuality(Asset $asset): JsonResponse
    {
        try {
            $updatedAsset = $this->assetService->calculateDataQuality($asset);

            $this->loggingService->logBusinessOperation('calculate_quality', 'asset', $asset->id);

            return response()->json([
                'message' => 'Data quality calculated successfully.',
                'data' => new AssetResource($updatedAsset),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to calculate data quality', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to calculate data quality.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/api/v1/assets/warranty/expiring-soon",
        summary: "Get assets with warranties expiring soon",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Assets with expiring warranties"
            )
        ]
    )]
    public function warrantyExpiringSoon(Request $request): AssetCollection
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        $assets = $this->assetService->getAssetsWithExpiringWarranties(
            perPage: (int) $request->get('per_page', 15)
        );

        $this->loggingService->logBusinessOperation('warranty_expiring_soon', 'asset', 'multiple');

        return new AssetCollection($assets);
    }

    #[OA\Get(
        path: "/api/v1/assets/warranty/expired",
        summary: "Get assets with expired warranties",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Assets with expired warranties"
            )
        ]
    )]
    public function warrantyExpired(Request $request): AssetCollection
    {
        $this->loggingService->logApiRequest($request->method(), $request->path());

        $assets = $this->assetService->getAssetsWithExpiredWarranties(
            perPage: (int) $request->get('per_page', 15)
        );

        $this->loggingService->logBusinessOperation('warranty_expired', 'asset', 'multiple');

        return new AssetCollection($assets);
    }

    #[OA\Get(
        path: "/api/v1/assets/statistics",
        summary: "Get asset statistics",
        tags: ["Assets"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asset statistics",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "total_assets", type: "integer"),
                                new OA\Property(property: "active_assets", type: "integer"),
                                new OA\Property(property: "inactive_assets", type: "integer"),
                                new OA\Property(property: "retired_assets", type: "integer"),
                                new OA\Property(property: "assets_with_warranties", type: "integer"),
                                new OA\Property(property: "expiring_warranties", type: "integer"),
                                new OA\Property(property: "expired_warranties", type: "integer"),
                                new OA\Property(property: "low_quality_assets", type: "integer"),
                                new OA\Property(property: "average_quality_score", type: "number"),
                                new OA\Property(property: "total_value", type: "number")
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->assetService->getStatistics();

            $this->loggingService->logBusinessOperation('statistics', 'asset', 'aggregate');

            return response()->json([
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            $this->loggingService->error('Failed to get asset statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve statistics.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
