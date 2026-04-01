<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateWorkspaceMemberActiveRequest;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\WorkspaceMemberActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceMemberController extends Controller
{
    public function __construct(
        private readonly WorkspaceMemberActivationService $workspaceMemberActivation,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $query = $workspace->members()->with('user')->orderBy('id');

        if (! $request->user()->isWorkspaceOwner($workspace)) {
            $query->where('active', true);
        }

        return WorkspaceMemberResource::collection($query->get())->response();
    }

    public function update(UpdateWorkspaceMemberActiveRequest $request, Workspace $workspace, User $user): JsonResponse
    {
        $member = WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('update', $member);

        $active = $request->boolean('active');
        $updated = $this->workspaceMemberActivation->setWorkspaceMemberActive($workspace, (int) $user->id, $active);
        $updated->load('user');

        return (new WorkspaceMemberResource($updated))->response();
    }

    /**
     * Desactiva la membresía (equivalente a PATCH active=false).
     */
    public function destroy(Request $request, Workspace $workspace, User $user): Response
    {
        $member = WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('delete', $member);

        $this->workspaceMemberActivation->setWorkspaceMemberActive($workspace, (int) $user->id, false);

        return response()->noContent();
    }
}
