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

class AssignmentAccessTest extends TestCase
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

    public function test_workspace_member_cannot_create_assignment(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = Workspace::query()->create([
            'owner_id' => $owner->id,
            'name' => 'WS',
            'code' => 'ws-m',
            'description' => null,
        ]);

        foreach ([$owner, $member] as $u) {
            WorkspaceMember::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $u->id,
                'role' => $u->is($owner) ? 'owner' : 'member',
            ]);
        }

        $this->actingAs($member)
            ->withHeaders($this->spaHeaders())
            ->postJson("/api/v1/workspaces/{$workspace->id}/assignments", [
                'name' => 'No permitida',
            ])
            ->assertForbidden();
    }

    public function test_assignment_index_only_lists_tasks_where_user_is_assignment_member_unless_workspace_owner(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = Workspace::query()->create([
            'owner_id' => $owner->id,
            'name' => 'WS',
            'code' => 'ws-list',
            'description' => null,
        ]);

        foreach ([$owner, $member] as $u) {
            WorkspaceMember::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $u->id,
                'role' => $u->is($owner) ? 'owner' : 'member',
            ]);
        }

        $assignmentA = Assignment::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'name' => 'Tarea A',
            'code' => 'ta',
            'description' => null,
            'deadline' => null,
            'status' => 'open',
        ]);
        AssignmentMember::query()->create([
            'assignment_id' => $assignmentA->id,
            'user_id' => $owner->id,
            'status' => 'pending',
        ]);
        AssignmentMember::query()->create([
            'assignment_id' => $assignmentA->id,
            'user_id' => $member->id,
            'status' => 'pending',
        ]);

        $assignmentB = Assignment::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'name' => 'Tarea B',
            'code' => 'tb',
            'description' => null,
            'deadline' => null,
            'status' => 'open',
        ]);
        AssignmentMember::query()->create([
            'assignment_id' => $assignmentB->id,
            'user_id' => $owner->id,
            'status' => 'pending',
        ]);

        $memberList = $this->actingAs($member)
            ->withHeaders($this->spaHeaders())
            ->getJson("/api/v1/workspaces/{$workspace->id}/assignments")
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $memberList);
        $this->assertSame($assignmentA->id, $memberList[0]['id']);

        $ownerList = $this->actingAs($owner)
            ->withHeaders($this->spaHeaders())
            ->getJson("/api/v1/workspaces/{$workspace->id}/assignments")
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $ownerList);
    }
}
