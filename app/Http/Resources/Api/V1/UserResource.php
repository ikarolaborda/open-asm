<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'title'         => $this->title,
            'department'    => $this->department,
            'is_active'     => $this->is_active,
            'organization'  => new OrganizationResource($this->whenLoaded('organization')),
            // **pluck only the names** so front-end sees ["admin","user"], not objects
            'roles'         => $this->roles->pluck('name'),
            'permissions'   => $this->permissions->pluck('name'),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
