<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

class WorkspaceMemberActivationService
{
    /**
     * Activa o desactiva la membresía en el workspace y replica el estado en todas las participaciones
     * de tareas de ese workspace para el mismo usuario.
     */
    public function setWorkspaceMemberActive(Workspace $workspace, int $userId, bool $active): WorkspaceMember
    {
        $member = WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $member->update(['active' => $active]);

        $assignmentIds = Assignment::query()
            ->where('workspace_id', $workspace->id)
            ->pluck('id');

        if ($assignmentIds->isNotEmpty()) {
            AssignmentMember::query()
                ->whereIn('assignment_id', $assignmentIds)
                ->where('user_id', $userId)
                ->update(['active' => $active]);
        }

        return $member->fresh();
    }
}
