<?php

declare(strict_types=1);

namespace App\Http\Resources\Asset;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AssetCollection",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/AssetResource"),
            description: "Array of asset resources"
        ),
        new OA\Property(
            property: "links",
            type: "object",
            properties: [
                new OA\Property(property: "first", type: "string", nullable: true, description: "First page URL"),
                new OA\Property(property: "last", type: "string", nullable: true, description: "Last page URL"),
                new OA\Property(property: "prev", type: "string", nullable: true, description: "Previous page URL"),
                new OA\Property(property: "next", type: "string", nullable: true, description: "Next page URL")
            ]
        ),
        new OA\Property(
            property: "meta",
            type: "object",
            properties: [
                new OA\Property(property: "current_page", type: "integer", description: "Current page number"),
                new OA\Property(property: "from", type: "integer", nullable: true, description: "First item number on current page"),
                new OA\Property(property: "last_page", type: "integer", description: "Last page number"),
                new OA\Property(property: "path", type: "string", description: "Base path for pagination"),
                new OA\Property(property: "per_page", type: "integer", description: "Items per page"),
                new OA\Property(property: "to", type: "integer", nullable: true, description: "Last item number on current page"),
                new OA\Property(property: "total", type: "integer", description: "Total number of items"),
                new OA\Property(
                    property: "filters",
                    type: "object",
                    description: "Applied filters",
                    nullable: true
                ),
                new OA\Property(property: "sort", type: "string", description: "Applied sorting", nullable: true)
            ]
        )
    ]
)]
class AssetCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     */
    public $collects = AssetResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'path' => $this->path(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
                'filters' => $request->get('filter'),
                'sort' => $request->get('sort'),
            ],
        ];
    }

    /**
     * Customize the pagination information for the resource.
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => array_merge($default['meta'], [
                'filters' => $request->get('filter'),
                'sort' => $request->get('sort'),
            ]),
        ];
    }
} 