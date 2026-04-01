<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAssignmentRequest;
use App\Http\Requests\Api\UpdateAssignmentRequest;
use App\Http\Requests\Api\UpdateAssignmentStatusRequest;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignmentController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $assignments = $workspace->assignments()
            ->with('workspace')
            ->orderByDesc('created_at')
            ->get();

        return AssignmentResource::collection($assignments)->response();
    }

    public function store(StoreAssignmentRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('create', [Assignment::class, $workspace]);

        $user = $request->user();
        $data = $request->validated();

        $assignment = Assignment::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);

        AssignmentMember::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $assignment->load('workspace');

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $assignment->loadMissing('workspace');

        return (new AssignmentResource($assignment))->response();
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $assignment->update($request->validated());

        return (new AssignmentResource($assignment->fresh()->load('workspace')))->response();
    }

    public function destroy(Request $request, Assignment $assignment): Response
    {
        $this->authorize('delete', $assignment);

        $assignment->delete();

        return response()->noContent();
    }

    public function updateStatus(UpdateAssignmentStatusRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('updateStatus', $assignment);

        $assignment->update(['status' => $request->validated('status')]);

        return (new AssignmentResource($assignment->fresh()->load('workspace')))->response();
    }
}
