<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function workspaceMemberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function createdAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'created_by');
    }

    public function assignmentMemberships(): HasMany
    {
        return $this->hasMany(AssignmentMember::class);
    }

    public function generatedMergedOutputs(): HasMany
    {
        return $this->hasMany(MergedOutput::class, 'generated_by');
    }

    public function canAccessWorkspace(Workspace $workspace): bool
    {
        if ($this->isWorkspaceOwner($workspace)) {
            return true;
        }

        if (! $workspace->active) {
            return false;
        }

        return $workspace->members()
            ->where('user_id', $this->id)
            ->where('active', true)
            ->exists();
    }

    public function isWorkspaceOwner(Workspace $workspace): bool
    {
        return (int) $workspace->owner_id === (int) $this->id;
    }

    /**
     * Puede administrar el workspace (dueño de registro o miembro con rol owner).
     */
    public function canAdminWorkspace(Workspace $workspace): bool
    {
        if ($this->isWorkspaceOwner($workspace)) {
            return true;
        }

        return $workspace->members()
            ->where('user_id', $this->id)
            ->where('role', 'owner')
            ->exists();
    }

    /**
     * Dueño del workspace ve todo; el resto solo si la tarea y el workspace están activos y su participación está activa.
     */
    public function canAccessAssignment(Assignment $assignment): bool
    {
        $workspace = $assignment->workspace;

        if ($this->isWorkspaceOwner($workspace)) {
            return true;
        }

        if (! $workspace->active || ! $assignment->active) {
            return false;
        }

        return $assignment->members()
            ->where('user_id', $this->id)
            ->where('active', true)
            ->exists();
    }
}
