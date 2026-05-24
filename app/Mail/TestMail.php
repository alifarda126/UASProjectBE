<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $timestamp;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        $this->timestamp = now()->toDateTimeString();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Test Email from MoneFlo Backend')
                    ->view('emails.test');
    }
}
