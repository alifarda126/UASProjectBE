<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    public string $otp;
    public string $action;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otp, string $action)
    {
        $this->otp    = $otp;
        $this->action = $action;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->action) {
            'register'       => 'Kode Verifikasi Registrasi MoneFlo',
            'update_email'   => 'Kode Verifikasi Perubahan Email MoneFlo',
            'forgot_password' => 'Kode Pemulihan Kata Sandi MoneFlo',
            default          => 'Kode Verifikasi Anda – MoneFlo',
        };

        return new Envelope(
            from: new Address(
                config('mail.from.address', 'noreply@moneflo.com'),
                config('mail.from.name', 'MoneFlo')
            ),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otp'    => $this->otp,
                'action' => $this->action,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
