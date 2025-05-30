<?php

declare(strict_types=1);

namespace App\Http\Resources\Asset;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AssetResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'Asset ID'),
        new OA\Property(property: 'organization_id', type: 'string', format: 'uuid', description: 'Organization ID'),
        new OA\Property(property: 'customer_id', type: 'string', format: 'uuid', description: 'Customer ID'),
        new OA\Property(property: 'location_id', type: 'string', format: 'uuid', nullable: true, description: 'Location ID'),
        new OA\Property(property: 'oem_id', type: 'string', format: 'uuid', nullable: true, description: 'OEM ID'),
        new OA\Property(property: 'product_id', type: 'string', format: 'uuid', nullable: true, description: 'Product ID'),
        new OA\Property(property: 'type_id', type: 'string', format: 'uuid', nullable: true, description: 'Type ID'),
        new OA\Property(property: 'status_id', type: 'string', format: 'uuid', nullable: true, description: 'Status ID'),
        new OA\Property(property: 'serial_number', type: 'string', description: 'Serial number'),
        new OA\Property(property: 'asset_tag', type: 'string', nullable: true, description: 'Asset tag'),
        new OA\Property(property: 'model_number', type: 'string', nullable: true, description: 'Model number'),
        new OA\Property(property: 'part_number', type: 'string', nullable: true, description: 'Part number'),
        new OA\Property(property: 'name', type: 'string', description: 'Asset name'),
        new OA\Property(property: 'description', type: 'string', nullable: true, description: 'Description'),
        new OA\Property(property: 'purchase_date', type: 'string', format: 'date', nullable: true, description: 'Purchase date'),
        new OA\Property(property: 'installation_date', type: 'string', format: 'date', nullable: true, description: 'Installation date'),
        new OA\Property(property: 'warranty_start_date', type: 'string', format: 'date', nullable: true, description: 'Warranty start date'),
        new OA\Property(property: 'warranty_end_date', type: 'string', format: 'date', nullable: true, description: 'Warranty end date'),
        new OA\Property(property: 'purchase_price', type: 'number', format: 'float', nullable: true, description: 'Purchase price'),
        new OA\Property(property: 'current_value', type: 'number', format: 'float', nullable: true, description: 'Current value'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Active status'),
        new OA\Property(property: 'data_quality_score', type: 'integer', description: 'Data quality score (0-100)'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, description: 'Additional metadata'),
        new OA\Property(property: 'warranty_status', type: 'string', description: 'Warranty status (active, expiring_soon, expired, no_warranty)'),
        new OA\Property(property: 'has_active_warranty', type: 'boolean', description: 'Whether asset has active warranty'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', description: 'Creation timestamp'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', description: 'Last update timestamp'),
        new OA\Property(property: 'organization', type: 'object', nullable: true, description: 'Organization details'),
        new OA\Property(property: 'customer', type: 'object', nullable: true, description: 'Customer details'),
        new OA\Property(property: 'location', type: 'object', nullable: true, description: 'Location details'),
        new OA\Property(property: 'oem', type: 'object', nullable: true, description: 'OEM details'),
        new OA\Property(property: 'product', type: 'object', nullable: true, description: 'Product details'),
        new OA\Property(property: 'type', type: 'object', nullable: true, description: 'Asset type details'),
        new OA\Property(property: 'status', type: 'object', nullable: true, description: 'Status details'),
        new OA\Property(property: 'warranties', type: 'array', items: new OA\Items(type: 'object'), description: 'Warranty records'),
        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'object'), description: 'Associated tags'),
    ]
)]
class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'customer_id' => $this->customer_id,
            'location_id' => $this->location_id,
            'oem_id' => $this->oem_id,
            'product_id' => $this->product_id,
            'type_id' => $this->type_id,
            'status_id' => $this->status_id,

            // Asset identification
            'serial_number' => $this->serial_number,
            'asset_tag' => $this->asset_tag,
            'model_number' => $this->model_number,
            'part_number' => $this->part_number,

            // Asset details
            'name' => $this->name,
            'description' => $this->description,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'installation_date' => $this->installation_date?->format('Y-m-d'),
            'warranty_start_date' => $this->warranty_start_date?->format('Y-m-d'),
            'warranty_end_date' => $this->warranty_end_date?->format('Y-m-d'),
            'purchase_price' => $this->purchase_price,
            'current_value' => $this->current_value,

            // Status and quality
            'is_active' => $this->is_active,
            'data_quality_score' => $this->data_quality_score,
            'metadata' => $this->metadata,

            // Computed attributes
            'warranty_status' => $this->warranty_status,
            'has_active_warranty' => $this->hasActiveWarranty(),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'organization' => $this->whenLoaded('organization'),
            'customer' => $this->whenLoaded('customer'),
            'location' => $this->whenLoaded('location'),
            'oem' => $this->whenLoaded('oem'),
            'product' => $this->whenLoaded('product'),
            'type' => $this->whenLoaded('type'),
            'status' => $this->whenLoaded('status'),
            'warranties' => $this->whenLoaded('warranties'),
            'tags' => $this->whenLoaded('tags'),
        ];
    }
}
