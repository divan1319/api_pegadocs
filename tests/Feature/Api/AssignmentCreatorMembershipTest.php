<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentCreatorMembershipTest extends TestCase
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

    public function test_creator_is_added_to_assignment_members_when_creating_assignment(): void
    {
        $user = User::factory()->create();

        $workspace = Workspace::query()->create([
            'owner_id' => $user->id,
            'name' => 'WS',
            'code' => 'ws-1',
            'description' => null,
        ]);

        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders($this->spaHeaders())
            ->postJson("/api/v1/workspaces/{$workspace->id}/assignments", [
                'name' => 'Tarea nueva',
                'status' => 'open',
            ]);

        $response->assertCreated();

        $assignmentId = (int) $response->json('data.id');

        $this->assertDatabaseHas('assignment_members', [
            'assignment_id' => $assignmentId,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }
}
