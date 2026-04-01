<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\MergedOutput;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MergedOutputService
{
    /**
     * @param  array<int, int>  $submissionIds
     */
    public function store(Assignment $assignment, User $user, UploadedFile $file, array $submissionIds = []): MergedOutput
    {
        if ($submissionIds !== []) {
            $unique = array_values(array_unique($submissionIds));
            $found = $assignment->submissions()->whereIn('id', $unique)->count();
            if ($found !== count($unique)) {
                throw new InvalidArgumentException('Algún id de entrega no pertenece a esta tarea.');
            }
        }

        $directory = 'merged_outputs/'.$assignment->id;
        $name = Str::uuid()->toString().'.pdf';
        $relativePath = $file->storeAs($directory, $name, 'public');

        return MergedOutput::query()->create([
            'assignment_id' => $assignment->id,
            'generated_by' => $user->id,
            'file_url' => $relativePath,
            'generated_at' => now(),
        ]);
    }
}
