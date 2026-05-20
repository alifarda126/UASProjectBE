<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $action;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($otp, $action)
    {
        $this->otp = $otp;
        $this->action = $action;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Kode Verifikasi Anda';
        if ($this->action === 'register') {
            $subject = 'Kode Verifikasi Registrasi MoneFlo';
        } elseif ($this->action === 'update_email') {
            $subject = 'Kode Verifikasi Perubahan Email MoneFlo';
        } elseif ($this->action === 'forgot_password') {
            $subject = 'Kode Pemulihan Kata Sandi MoneFlo';
        }

        return $this->subject($subject)
                    ->view('emails.otp');
    }
}
