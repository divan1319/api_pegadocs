<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceApiTest extends TestCase
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

    public function test_workspaces_index_requires_authentication(): void
    {
        $this->withHeaders($this->spaHeaders())
            ->getJson('/api/v1/workspaces')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_and_list_workspace(): void
    {
        $user = User::factory()->create();

        $create = $this->actingAs($user)
            ->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/workspaces', [
                'name' => 'Grupo A',
                'code' => 'grupo-a-unico',
                'description' => 'Desc',
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Grupo A')
            ->assertJsonPath('data.code', 'grupo-a-unico');

        $list = $this->actingAs($user)
            ->withHeaders($this->spaHeaders())
            ->getJson('/api/v1/workspaces');

        $list->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_join_workspace_by_code(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();

        $workspace = Workspace::query()->create([
            'owner_id' => $owner->id,
            'name' => 'WS',
            'code' => 'codigo-compartido',
            'description' => null,
        ]);

        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->actingAs($joiner)
            ->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/workspaces/join', [
                'code' => 'codigo-compartido',
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'codigo-compartido');

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $joiner->id,
            'role' => 'member',
        ]);
    }
}
