<?php

declare(strict_types=1);

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'industry' => $this->industry,
            'description' => $this->description,

            // Billing address
            'billing_address' => $this->billing_address,
            'billing_city' => $this->billing_city,
            'billing_state' => $this->billing_state,
            'billing_country' => $this->billing_country,
            'billing_postal_code' => $this->billing_postal_code,
            'full_billing_address' => $this->full_billing_address,

            // Status and metadata
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,

            // Computed attributes
            'assets_count' => $this->whenLoaded('assets', fn () => $this->assets->count()),
            'locations_count' => $this->whenLoaded('locations', fn () => $this->locations->count()),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->when($this->deleted_at, fn () => $this->deleted_at?->toISOString()),

            // Relationships
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'code' => $this->organization->code,
            ]),

            'assets' => $this->whenLoaded('assets', function () {
                return $this->assets->map(fn ($asset) => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'serial_number' => $asset->serial_number,
                    'asset_tag' => $asset->asset_tag,
                    'is_active' => $asset->is_active,
                    'created_at' => $asset->created_at?->toISOString(),
                ]);
            }),

            'locations' => $this->whenLoaded('locations', function () {
                return $this->locations->map(fn ($location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'address' => $location->address,
                    'city' => $location->city,
                    'country' => $location->country,
                    'is_headquarters' => $location->is_headquarters,
                    'is_active' => $location->is_active,
                ]);
            }),

            'contacts' => $this->whenLoaded('contacts', function () {
                return $this->contacts->map(fn ($contact) => [
                    'id' => $contact->id,
                    'full_name' => $contact->full_name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'title' => $contact->title,
                    'contact_type' => $contact->pivot->contact_type,
                    'is_primary' => (bool) $contact->pivot->is_primary,
                ]);
            }),

            'statuses' => $this->whenLoaded('statuses', function () {
                return $this->statuses->map(fn ($status) => [
                    'id' => $status->id,
                    'name' => $status->name,
                    'code' => $status->code,
                    'color' => $status->color,
                    'is_current' => (bool) $status->pivot->is_current,
                ]);
            }),

            'current_status' => $this->whenLoaded('currentStatus', function () {
                $currentStatus = $this->currentStatus->first();

                return $currentStatus ? [
                    'id' => $currentStatus->id,
                    'name' => $currentStatus->name,
                    'code' => $currentStatus->code,
                    'color' => $currentStatus->color,
                ] : null;
            }),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'links' => [
                    'self' => route('api.v1.customers.show', $this->id),
                    'assets' => route('api.v1.customers.assets.index', $this->id),
                    'locations' => route('api.v1.customers.locations.index', $this->id),
                ],
            ],
        ];
    }
}
