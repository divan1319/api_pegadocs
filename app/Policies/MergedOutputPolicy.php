<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\MergedOutput;
use App\Models\User;

class MergedOutputPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MergedOutput $mergedOutput): bool
    {
        return $user->canAccessAssignment($mergedOutput->assignment);
    }

    public function create(User $user, Assignment $assignment): bool
    {
        return $user->canAccessAssignment($assignment);
    }

    public function update(User $user, MergedOutput $mergedOutput): bool
    {
        return false;
    }

    public function delete(User $user, MergedOutput $mergedOutput): bool
    {
        return $user->canManageAssignment($mergedOutput->assignment);
    }

    public function restore(User $user, MergedOutput $mergedOutput): bool
    {
        return false;
    }

    public function forceDelete(User $user, MergedOutput $mergedOutput): bool
    {
        return false;
    }
}
