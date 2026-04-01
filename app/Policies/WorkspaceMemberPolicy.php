<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceMember;

class WorkspaceMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WorkspaceMember $workspaceMember): bool
    {
        return $user->canAccessWorkspace($workspaceMember->workspace);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WorkspaceMember $workspaceMember): bool
    {
        return $this->ownerMayManageMember($user, $workspaceMember);
    }

    public function delete(User $user, WorkspaceMember $workspaceMember): bool
    {
        return $this->ownerMayManageMember($user, $workspaceMember);
    }

    public function restore(User $user, WorkspaceMember $workspaceMember): bool
    {
        return false;
    }

    public function forceDelete(User $user, WorkspaceMember $workspaceMember): bool
    {
        return false;
    }

    private function ownerMayManageMember(User $user, WorkspaceMember $workspaceMember): bool
    {
        $workspace = $workspaceMember->workspace;

        if (! $user->isWorkspaceOwner($workspace)) {
            return false;
        }

        return (int) $workspaceMember->user_id !== (int) $workspace->owner_id;
    }
}
