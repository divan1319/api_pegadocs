<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignmentId' => $this->assignment_id,
            'assignmentMemberId' => $this->assignment_member_id,
            'fileName' => $this->file_name,
            'fileUrl' => url("/api/v1/submissions/{$this->id}/file"),
            'convertedPdfUrl' => $this->converted_pdf_url
                ? url("/api/v1/submissions/{$this->id}/file?variant=converted")
                : null,
            'fileType' => $this->file_type,
            'fileSize' => $this->file_size,
            'status' => $this->status,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
