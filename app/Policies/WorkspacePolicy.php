<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $user->canAccessWorkspace($workspace);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->canAdminWorkspace($workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->isWorkspaceOwner($workspace);
    }

    public function restore(User $user, Workspace $workspace): bool
    {
        return false;
    }

    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return false;
    }
}
