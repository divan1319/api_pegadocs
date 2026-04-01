<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\User;

class AssignmentMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AssignmentMember $assignmentMember): bool
    {
        return $user->canAccessAssignment($assignmentMember->assignment);
    }

    public function create(User $user, Assignment $assignment): bool
    {
        return $user->canManageAssignment($assignment);
    }

    public function update(User $user, AssignmentMember $assignmentMember): bool
    {
        return $user->canManageAssignment($assignmentMember->assignment);
    }

    public function delete(User $user, AssignmentMember $assignmentMember): bool
    {
        return $user->canManageAssignment($assignmentMember->assignment);
    }

    public function restore(User $user, AssignmentMember $assignmentMember): bool
    {
        return false;
    }

    public function forceDelete(User $user, AssignmentMember $assignmentMember): bool
    {
        return false;
    }
}
