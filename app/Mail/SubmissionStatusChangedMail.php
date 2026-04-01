<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
    ) {
        $this->submission->loadMissing([
            'assignmentMember.user',
            'assignment.workspace',
        ]);
    }

    public function envelope(): Envelope
    {
        $estado = match ($this->submission->status) {
            'accepted' => 'aceptada',
            'rejected' => 'rechazada',
            default => $this->submission->status,
        };

        return new Envelope(
            subject: sprintf('Tu entrega fue %s — %s', $estado, config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.submission-status-changed',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
