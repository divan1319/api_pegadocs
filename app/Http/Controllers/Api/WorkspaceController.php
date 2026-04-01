<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JoinWorkspaceRequest;
use App\Http\Requests\Api\StoreWorkspaceRequest;
use App\Http\Requests\Api\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workspace::class);

        $user = $request->user();
        $workspaces = Workspace::query()
            ->where(function ($q) use ($user): void {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
            })
            ->orderBy('name')
            ->get();

        return WorkspaceResource::collection($workspaces)->response();
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $this->authorize('create', Workspace::class);

        $user = $request->user();
        $data = $request->validated();

        $workspace = Workspace::query()->create([
            'owner_id' => $user->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        return (new WorkspaceResource($workspace))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        return (new WorkspaceResource($workspace))->response();
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        $workspace->update($request->validated());

        return (new WorkspaceResource($workspace->fresh()))->response();
    }

    public function destroy(Request $request, Workspace $workspace): Response
    {
        $this->authorize('delete', $workspace);

        $workspace->delete();

        return response()->noContent();
    }

    public function join(JoinWorkspaceRequest $request): JsonResponse
    {
        $code = $request->validated('code');
        $workspace = Workspace::query()->where('code', $code)->firstOrFail();
        $user = $request->user();

        if ($user->canAccessWorkspace($workspace)) {
            return (new WorkspaceResource($workspace))->response();
        }

        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        return (new WorkspaceResource($workspace->fresh()))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
