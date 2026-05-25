<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BandingRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $orgName;
    public $adminNote;

    /**
     * Create a new message instance.
     */
    public function __construct($orgName, $adminNote)
    {
        $this->orgName = $orgName;
        $this->adminNote = $adminNote;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan: Pengajuan Banding Ditolak',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.banding-rejected',
            with: [
                'orgName' => $this->orgName,
                'adminNote' => $this->adminNote,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
