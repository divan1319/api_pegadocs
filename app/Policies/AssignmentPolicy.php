<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Workspace;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->canAccessAssignment($assignment);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $user->isWorkspaceOwner($workspace);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isWorkspaceOwner($assignment->workspace);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->isWorkspaceOwner($assignment->workspace);
    }

    public function updateStatus(User $user, Assignment $assignment): bool
    {
        return $user->isWorkspaceOwner($assignment->workspace);
    }

    public function restore(User $user, Assignment $assignment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Assignment $assignment): bool
    {
        return false;
    }
}
