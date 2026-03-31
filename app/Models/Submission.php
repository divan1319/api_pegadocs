<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'assignment_id',
    'assignment_member_id',
    'file_name',
    'file_url',
    'converted_pdf_url',
    'file_type',
    'file_size',
    'status',
])]
class Submission extends Model
{
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function assignmentMember(): BelongsTo
    {
        return $this->belongsTo(AssignmentMember::class);
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }
}
