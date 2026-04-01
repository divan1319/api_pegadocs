<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMergedOutputRequest;
use App\Http\Resources\MergedOutputResource;
use App\Models\Assignment;
use App\Models\MergedOutput;
use App\Services\MergedOutputService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class MergedOutputController extends Controller
{
    public function __construct(
        private readonly MergedOutputService $mergedOutputService,
    ) {}

    public function index(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $outputs = $assignment->mergedOutputs()->orderByDesc('generated_at')->get();

        return MergedOutputResource::collection($outputs)->response();
    }

    public function store(StoreMergedOutputRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('create', [MergedOutput::class, $assignment]);

        $submissionIds = $request->validated('submission_ids') ?? [];

        try {
            $output = $this->mergedOutputService->store(
                $assignment,
                $request->user(),
                $request->file('file'),
                $submissionIds
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'submission_ids' => [$e->getMessage()],
            ]);
        }

        return (new MergedOutputResource($output))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
