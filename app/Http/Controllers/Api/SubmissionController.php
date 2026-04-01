<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSubmissionRequest;
use App\Http\Requests\Api\UpdateSubmissionStatusRequest;
use App\Http\Resources\SubmissionResource;
use App\Mail\SubmissionStatusChangedMail;
use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissionService,
    ) {}

    public function index(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $submissions = $assignment->submissions()->orderByDesc('created_at')->get();

        return SubmissionResource::collection($submissions)->response();
    }

    public function store(StoreSubmissionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('create', [Submission::class, $assignment]);

        $memberId = (int) $request->validated('assignment_member_id');
        $assignmentMember = AssignmentMember::query()->findOrFail($memberId);

        if ((int) $assignmentMember->assignment_id !== (int) $assignment->id) {
            throw ValidationException::withMessages([
                'assignment_member_id' => ['La participación no corresponde a esta tarea.'],
            ]);
        }

        if ((int) $assignmentMember->user_id !== (int) $request->user()->id) {
            throw ValidationException::withMessages([
                'assignment_member_id' => ['Solo puedes subir archivos para tu propia participación.'],
            ]);
        }

        try {
            $submission = $this->submissionService->store(
                $assignment,
                $assignmentMember,
                $request->file('file')
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'file' => [$e->getMessage()],
            ]);
        }

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Submission $submission): Response
    {
        $this->authorize('delete', $submission);

        $disk = Storage::disk('public');
        $disk->delete($submission->file_url);
        if ($submission->converted_pdf_url) {
            $disk->delete($submission->converted_pdf_url);
        }

        $submission->delete();

        return response()->noContent();
    }

    public function updateStatus(UpdateSubmissionStatusRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        $submission->load(['assignmentMember.user', 'assignment.workspace']);
        $previous = $submission->status;
        $submission->update(['status' => $request->validated('status')]);
        $submission->refresh();

        $new = $submission->status;
        if (in_array($new, ['accepted', 'rejected'], true) && $previous !== $new) {
            $recipient = $submission->assignmentMember->user;
            if ($recipient !== null) {
                try {
                    Mail::to($recipient)->send(new SubmissionStatusChangedMail($submission));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return (new SubmissionResource($submission))->response();
    }
}
