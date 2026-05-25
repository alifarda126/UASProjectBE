<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $announcement;
    public $appName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($announcement, $appName = 'MoneFlo')
    {
        $this->announcement = $announcement;
        $this->appName = $appName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Pengumuman Sistem: ' . $this->appName;
        
        return $this->subject($subject)
                    ->view('emails.announcement');
    }
}
