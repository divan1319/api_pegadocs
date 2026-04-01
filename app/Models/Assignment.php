<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['workspace_id', 'created_by', 'name', 'code', 'description', 'deadline', 'status', 'active'])]
class Assignment extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Assignment $assignment): void {
            if (blank($assignment->code)) {
                $assignment->code = (string) Str::upper(Str::random(8));
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AssignmentMember::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function mergedOutputs(): HasMany
    {
        return $this->hasMany(MergedOutput::class);
    }

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'active' => 'boolean',
        ];
    }
}
