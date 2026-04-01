<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAssignmentMemberRequest;
use App\Http\Requests\Api\UpdateAssignmentMemberStatusRequest;
use App\Http\Resources\AssignmentMemberResource;
use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AssignmentMemberController extends Controller
{
    public function index(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $members = $assignment->members()->with('user')->orderBy('id')->get();

        return AssignmentMemberResource::collection($members)->response();
    }

    public function store(StoreAssignmentMemberRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('create', [AssignmentMember::class, $assignment]);

        $workspace = $assignment->workspace;
        $userId = (int) $request->validated('user_id');
        $targetUser = User::query()->findOrFail($userId);

        if (! $targetUser->canAccessWorkspace($workspace)) {
            throw ValidationException::withMessages([
                'user_id' => ['El usuario debe ser miembro del workspace.'],
            ]);
        }

        if ($assignment->members()->where('user_id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['El usuario ya participa en esta tarea.'],
            ]);
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

        $member->delete();

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
}
