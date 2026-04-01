<?php

namespace Tests\Feature\Api;

use App\Mail\SubmissionStatusChangedMail;
use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubmissionStatusMailTest extends TestCase
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

    public function test_accepting_submission_sends_mail_to_uploader(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = Workspace::query()->create([
            'owner_id' => $owner->id,
            'name' => 'WS',
            'code' => 'ws-code',
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
        ]);

        $assignment = Assignment::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'name' => 'Tarea 1',
            'code' => 't1-code',
            'description' => null,
            'deadline' => null,
            'status' => 'open',
        ]);

        $assignmentMember = AssignmentMember::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $member->id,
            'status' => 'uploaded',
        ]);

        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'assignment_member_id' => $assignmentMember->id,
            'file_name' => 'doc.pdf',
            'file_url' => 'submissions/'.$assignment->id.'/doc.pdf',
            'converted_pdf_url' => null,
            'file_type' => 'pdf',
            'file_size' => 100,
            'status' => 'pending_review',
        ]);

        $this->actingAs($owner)
            ->withHeaders($this->spaHeaders())
            ->patchJson('/api/v1/submissions/'.$submission->id.'/status', [
                'status' => 'accepted',
            ])
            ->assertOk();

        Mail::assertSent(SubmissionStatusChangedMail::class, function (SubmissionStatusChangedMail $mail) use ($member, $submission): bool {
            return $mail->hasTo($member->email)
                && $mail->submission->is($submission)
                && $mail->submission->status === 'accepted';
        });
    }
}
