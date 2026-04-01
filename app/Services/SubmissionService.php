<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentMember;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SubmissionService
{
    public function store(Assignment $assignment, AssignmentMember $assignmentMember, UploadedFile $file): Submission
    {
        if ((int) $assignmentMember->assignment_id !== (int) $assignment->id) {
            throw new InvalidArgumentException('El miembro de la tarea no pertenece a esta asignación.');
        }

        $mime = $file->getMimeType() ?? 'application/octet-stream';
        $fileType = $this->mimeToFileType($mime);
        $extension = $file->guessExtension() ?: 'bin';
        $directory = 'submissions/'.$assignment->id;
        $storedName = Str::uuid()->toString().'.'.$extension;
        $relativePath = $file->storeAs($directory, $storedName, 'public');

        $convertedRelative = null;
        if ($fileType === 'image') {
            $convertedRelative = $this->convertImageToPdfIfSupported(
                Storage::disk('public')->path($relativePath),
                $directory,
                pathinfo($storedName, PATHINFO_FILENAME)
            );
        }

        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'assignment_member_id' => $assignmentMember->id,
            'file_name' => $file->getClientOriginalName(),
            'file_url' => $relativePath,
            'converted_pdf_url' => $convertedRelative,
            'file_type' => $fileType,
            'file_size' => $file->getSize() ?: 0,
            'status' => 'pending_review',
        ]);

        $assignmentMember->update(['status' => 'uploaded']);

        return $submission;
    }

    private function mimeToFileType(string $mime): string
    {
        return match (true) {
            $mime === 'application/pdf' => 'pdf',
            str_starts_with($mime, 'image/') => 'image',
            default => 'other',
        };
    }

    /**
     * Si la extensión imagick está disponible, genera un PDF junto a la imagen.
     */
    private function convertImageToPdfIfSupported(string $absoluteImagePath, string $directory, string $basenameWithoutExt): ?string
    {
        if (! extension_loaded('imagick')) {
            return null;
        }

        try {
            $imagick = new \Imagick;
            $imagick->readImage($absoluteImagePath);
            $imagick->setImageFormat('pdf');
            $pdfName = $basenameWithoutExt.'.pdf';
            $relative = $directory.'/'.$pdfName;
            $fullPdfPath = Storage::disk('public')->path($relative);
            $imagick->writeImages($fullPdfPath, true);
            $imagick->clear();
            $imagick->destroy();

            return $relative;
        } catch (\Throwable) {
            return null;
        }
    }
}
