<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organization\Services\OrganizationService;
use App\Http\Controllers\Controller;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Organizations', description: 'Organization management endpoints')]
class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationService $organizationService,
        private TenantService $tenantService
    ) {}

    #[OA\Get(
        path: '/api/v1/organization',
        summary: 'Get current organization details',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Organization details retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/OrganizationResource')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 403,
                description: 'No organization assigned',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function show(): JsonResponse
    {
        $organization = $this->tenantService->getCurrentOrganization();

        if (! $organization) {
            return response()->json([
                'message' => 'No organization assigned',
                'error' => 'User is not assigned to any organization.',
            ], 403);
        }

        return response()->json([
            'data' => $organization,
            'meta' => [
                'statistics' => $organization->getStatistics(),
                'health' => $this->organizationService->getHealthStatus($organization),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/organization/overview',
        summary: 'Get current organization overview with detailed information',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Organization overview retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/OrganizationResource'),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'statistics', type: 'object'),
                                new OA\Property(property: 'recent_customers', type: 'array', items: new OA\Items(ref: '#/components/schemas/CustomerResource')),
                                new OA\Property(property: 'recent_assets', type: 'array', items: new OA\Items(ref: '#/components/schemas/AssetResource')),
                                new OA\Property(property: 'health', type: 'object'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function overview(): JsonResponse
    {
        $organization = $this->tenantService->getCurrentOrganization();

        if (! $organization) {
            return response()->json([
                'message' => 'No organization assigned',
                'error' => 'User is not assigned to any organization.',
            ], 403);
        }

        $overview = $this->organizationService->getOverview($organization);

        return response()->json([
            'data' => $overview['organization'],
            'meta' => [
                'statistics' => $overview['statistics'],
                'recent_customers' => $overview['recent_customers'],
                'recent_assets' => $overview['recent_assets'],
                'active_users' => $overview['active_users'],
                'health' => $this->organizationService->getHealthStatus($organization),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/organization/statistics',
        summary: 'Get current organization statistics',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Organization statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'users_count', type: 'integer'),
                                new OA\Property(property: 'customers_count', type: 'integer'),
                                new OA\Property(property: 'assets_count', type: 'integer'),
                                new OA\Property(property: 'locations_count', type: 'integer'),
                                new OA\Property(property: 'active_assets_count', type: 'integer'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function statistics(): JsonResponse
    {
        $statistics = $this->tenantService->getCurrentOrganizationStatistics();

        return response()->json([
            'data' => $statistics,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/organization/health',
        summary: 'Get current organization health status',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Organization health status retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'overall_health', type: 'string', enum: ['excellent', 'good', 'fair', 'needs_attention']),
                                new OA\Property(
                                    property: 'checks',
                                    properties: [
                                        new OA\Property(property: 'is_active', type: 'boolean'),
                                        new OA\Property(property: 'has_users', type: 'boolean'),
                                        new OA\Property(property: 'has_customers', type: 'boolean'),
                                        new OA\Property(property: 'has_assets', type: 'boolean'),
                                        new OA\Property(property: 'active_ratio', type: 'number', format: 'float'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'recommendations', type: 'array', items: new OA\Items(type: 'string')),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function health(): JsonResponse
    {
        $organization = $this->tenantService->getCurrentOrganization();

        if (! $organization) {
            return response()->json([
                'message' => 'No organization assigned',
                'error' => 'User is not assigned to any organization.',
            ], 403);
        }

        $health = $this->organizationService->getHealthStatus($organization);

        return response()->json([
            'data' => $health,
        ]);
    }
}
