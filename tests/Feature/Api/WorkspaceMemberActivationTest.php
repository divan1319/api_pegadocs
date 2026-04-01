<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceMemberActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function spaHeaders(array $headers = []): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Referer' => $baseUrl.'/',
        ], $headers);
    }

    public function test_owner_deactivating_workspace_member_cascades_to_assignment_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = Workspace::query()->create([
            'owner_id' => $owner->id,
            'name' => 'WS',
            'code' => 'ws-cascade',
            'description' => null,
        ]);

        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'member',
            'active' => true,
        ]);

        $assignment = Assignment::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'name' => 'Tarea',
            'status' => 'open',
        ]);

        $assignmentMember = AssignmentMember::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $member->id,
            'status' => 'pending',
            'active' => true,
        ]);

        $this->actingAs($owner)
            ->withHeaders($this->spaHeaders())
            ->patchJson("/api/v1/workspaces/{$workspace->id}/members/{$member->id}", [
                'active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.active', false);

        $this->assertDatabaseHas('workspace_members', [
            'id' => $workspace->id,
            'user_id' => $member->id,
            'active' => false,
        ]);

        $this->assertDatabaseHas('assignment_members', [
            'id' => $assignmentMember->id,
            'active' => false,
        ]);
    }
}
