<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AssignmentMemberController;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\MergedOutputController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:login')->group(function (): void {
        Route::post('/auth/login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware('throttle:register')->group(function (): void {
        Route::post('/auth/register', [RegisteredUserController::class, 'store']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/user', function (Request $request) {
            return new UserResource($request->user());
        });
        Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);

        Route::post('/workspaces/join', [WorkspaceController::class, 'join']);
        Route::apiResource('workspaces', WorkspaceController::class);

        Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
        Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy']);

        Route::get('workspaces/{workspace}/assignments', [AssignmentController::class, 'index']);
        Route::post('workspaces/{workspace}/assignments', [AssignmentController::class, 'store']);

        Route::get('assignments/{assignment}', [AssignmentController::class, 'show']);
        Route::put('assignments/{assignment}', [AssignmentController::class, 'update']);
        Route::patch('assignments/{assignment}', [AssignmentController::class, 'update']);
        Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy']);
        Route::patch('assignments/{assignment}/status', [AssignmentController::class, 'updateStatus']);

        Route::get('assignments/{assignment}/members', [AssignmentMemberController::class, 'index']);
        Route::post('assignments/{assignment}/members', [AssignmentMemberController::class, 'store']);
        Route::delete('assignments/{assignment}/members/{user}', [AssignmentMemberController::class, 'destroy']);
        Route::patch('assignments/{assignment}/members/{user}/status', [AssignmentMemberController::class, 'updateStatus']);

        Route::get('assignments/{assignment}/submissions', [SubmissionController::class, 'index']);
        Route::post('assignments/{assignment}/submissions', [SubmissionController::class, 'store']);

        Route::get('submissions/{submission}/file', [SubmissionController::class, 'download']);
        Route::delete('submissions/{submission}', [SubmissionController::class, 'destroy']);
        Route::patch('submissions/{submission}/status', [SubmissionController::class, 'updateStatus']);

        Route::get('assignments/{assignment}/merged-outputs', [MergedOutputController::class, 'index']);
        Route::post('assignments/{assignment}/merged-outputs', [MergedOutputController::class, 'store']);
        Route::get('merged-outputs/{mergedOutput}/file', [MergedOutputController::class, 'download']);
    });
});
