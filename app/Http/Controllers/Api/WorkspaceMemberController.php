<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $members = $workspace->members()->with('user')->orderBy('id')->get();

        return WorkspaceMemberResource::collection($members)->response();
    }

    public function destroy(Request $request, Workspace $workspace, User $user): Response
    {
        $member = WorkspaceMember::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('delete', $member);

        $member->delete();

        return response()->noContent();
    }
}
