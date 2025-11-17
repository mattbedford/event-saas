<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class RegistrationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Registration $registration
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registration Confirmation - ' . $this->registration->event->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-confirmation',
            with: [
                'registration' => $this->registration,
                'event' => $this->registration->event,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        // Attach badge if generated
        if ($this->registration->badge_generated && $this->registration->badge_file_path) {
            $attachments[] = Attachment::fromPath(
                storage_path('app/' . $this->registration->badge_file_path)
            )->as('event-badge.pdf')
              ->withMime('application/pdf');
        }

        return $attachments;
    }
}
