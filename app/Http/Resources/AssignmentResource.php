<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspaceId' => $this->workspace_id,
            'createdBy' => $this->created_by,
            'workspaceOwnerId' => $this->when(
                $this->resource->relationLoaded('workspace'),
                (int) $this->workspace->owner_id,
            ),
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'deadline' => $this->deadline?->toIso8601String(),
            'status' => $this->status,
            'active' => $this->active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
