<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Registration $registration
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: ' . $this->registration->event->name . ' is Coming Up!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-reminder',
            with: [
                'registration' => $this->registration,
                'event' => $this->registration->event,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
