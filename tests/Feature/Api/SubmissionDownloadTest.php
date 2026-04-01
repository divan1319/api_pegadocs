<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionDownloadTest extends TestCase
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
            'Accept' => 'application/pdf',
            'Referer' => $baseUrl.'/',
        ], $headers);
    }

    public function test_authorized_user_can_download_submission_file_via_api(): void
    {
        $user = User::factory()->create();

        $workspace = Workspace::query()->create([
            'owner_id' => $user->id,
            'name' => 'WS',
            'code' => 'ws-dl',
            'description' => null,
        ]);

        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $assignment = Assignment::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'name' => 'T1',
            'code' => 't1-dl',
            'description' => null,
            'deadline' => null,
            'status' => 'open',
        ]);

        $member = AssignmentMember::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'status' => 'uploaded',
        ]);

        $relative = 'submissions/'.$assignment->id.'/prueba.pdf';
        Storage::disk('public')->put($relative, '%PDF-1.4 prueba');

        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'assignment_member_id' => $member->id,
            'file_name' => 'prueba.pdf',
            'file_url' => $relative,
            'converted_pdf_url' => null,
            'file_type' => 'pdf',
            'file_size' => 20,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders($this->spaHeaders())
            ->get('/api/v1/submissions/'.$submission->id.'/file');

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('%PDF', $response->streamedContent());
    }
}
