<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccessApproved extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    /**
     * @param string $email El email del usuario aprobado
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function build()
    {
        return $this
            ->subject('✅ Tu cuenta ha sido aprobada')
            ->view('emails.access_approved')
            ->with(['email' => $this->email]);
    }
}
