<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Collection $groupedNotifications;

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $groupedNotifications)
    {
        $this->groupedNotifications = $groupedNotifications;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $totalCount = $this->groupedNotifications->flatten()->count();

        return new Envelope(
            subject: "You have {$totalCount} new notification".($totalCount > 1 ? 's' : '').' on KeNHAVATE',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications',
            with: [
                'groupedNotifications' => $this->groupedNotifications,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
