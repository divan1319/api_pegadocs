<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Submission $submission): bool
    {
        return $user->canAccessAssignment($submission->assignment);
    }

    public function create(User $user, Assignment $assignment): bool
    {
        return $user->canAccessAssignment($assignment);
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->canManageAssignment($submission->assignment);
    }

    public function delete(User $user, Submission $submission): bool
    {
        if ($user->canManageAssignment($submission->assignment)) {
            return true;
        }

        $submission->loadMissing('assignmentMember');

        return (int) $submission->assignmentMember->user_id === (int) $user->id;
    }

    public function restore(User $user, Submission $submission): bool
    {
        return false;
    }

    public function forceDelete(User $user, Submission $submission): bool
    {
        return false;
    }
}
