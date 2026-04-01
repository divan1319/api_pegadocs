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
use Illuminate\Validation\ValidationException;
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
                    ->orWhere(function ($q2) use ($user): void {
                        $q2->where('active', true)
                            ->whereHas('members', function ($m) use ($user): void {
                                $m->where('user_id', $user->id)->where('active', true);
                            });
                    });
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

        if (! $workspace->active) {
            throw ValidationException::withMessages([
                'code' => ['Este workspace no está activo y no admite nuevas incorporaciones.'],
            ]);
        }

        $existing = WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            if (! $existing->active) {
                throw ValidationException::withMessages([
                    'code' => ['Tu acceso a este workspace fue desactivado. Contacta al dueño si necesitas volver a entrar.'],
                ]);
            }

            return (new WorkspaceResource($workspace))->response();
        }

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
