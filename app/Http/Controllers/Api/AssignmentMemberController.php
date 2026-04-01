<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAssignmentMemberRequest;
use App\Http\Requests\Api\UpdateAssignmentMemberActiveRequest;
use App\Http\Requests\Api\UpdateAssignmentMemberStatusRequest;
use App\Http\Resources\AssignmentMemberResource;
use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AssignmentMemberController extends Controller
{
    public function index(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $query = $assignment->members()->with('user')->orderBy('id');

        if (! $request->user()->isWorkspaceOwner($assignment->workspace)) {
            $query->where('active', true);
        }

        return AssignmentMemberResource::collection($query->get())->response();
    }

    public function store(StoreAssignmentMemberRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('create', [AssignmentMember::class, $assignment]);

        $workspace = $assignment->workspace;
        $userId = (int) $request->validated('user_id');
        User::query()->findOrFail($userId);

        $workspaceMember = WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $userId)
            ->where('active', true)
            ->first();

        if ($workspaceMember === null) {
            throw ValidationException::withMessages([
                'user_id' => ['El usuario debe ser miembro activo del workspace.'],
            ]);
        }

        $existing = AssignmentMember::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing !== null) {
            if ($existing->active) {
                throw ValidationException::withMessages([
                    'user_id' => ['El usuario ya participa en esta tarea.'],
                ]);
            }

            $existing->update([
                'active' => true,
                'status' => 'pending',
            ]);
            $existing->load('user');

            return (new AssignmentMemberResource($existing->fresh()))
                ->response()
                ->setStatusCode(Response::HTTP_OK);
        }

        $member = AssignmentMember::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $userId,
            'status' => 'pending',
        ]);

        $member->load('user');

        return (new AssignmentMemberResource($member))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Assignment $assignment, User $user): Response
    {
        $member = AssignmentMember::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('delete', $member);

        $member->update(['active' => false]);

        return response()->noContent();
    }

    public function updateStatus(UpdateAssignmentMemberStatusRequest $request, Assignment $assignment, User $user): JsonResponse
    {
        $member = AssignmentMember::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('update', $member);

        $member->update(['status' => $request->validated('status')]);

        $member->load('user');

        return (new AssignmentMemberResource($member->fresh()))->response();
    }

    public function updateActivation(UpdateAssignmentMemberActiveRequest $request, Assignment $assignment, User $user): JsonResponse
    {
        $member = AssignmentMember::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('update', $member);

        if ($request->boolean('active')) {
            $workspaceMember = WorkspaceMember::query()
                ->where('workspace_id', $assignment->workspace_id)
                ->where('user_id', $user->id)
                ->where('active', true)
                ->first();

            if ($workspaceMember === null) {
                throw ValidationException::withMessages([
                    'active' => ['El usuario debe seguir siendo miembro activo del workspace para reactivar su participación en la tarea.'],
                ]);
            }
        }

        $member->update(['active' => $request->boolean('active')]);

        $member->load('user');

        return (new AssignmentMemberResource($member->fresh()))->response();
    }
}
