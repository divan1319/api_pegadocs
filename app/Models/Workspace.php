<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['owner_id', 'name', 'code', 'description', 'active'])]
class Workspace extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Workspace $workspace): void {
            if (blank($workspace->code)) {
                $workspace->code = (string) Str::upper(Str::random(8));
            }
        });
    }
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
