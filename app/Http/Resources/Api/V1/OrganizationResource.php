<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'code'         => $this->code,
            'is_active'    => $this->is_active,
            'description'  => $this->description,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'website'      => $this->website,
            'address'      => $this->address,
            'city'         => $this->city,
            'state'        => $this->state,
            'country'      => $this->country,
            'postal_code'  => $this->postal_code,
            'metadata'     => $this->metadata,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
