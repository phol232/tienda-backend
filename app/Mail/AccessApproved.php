<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccessApproved extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function build()
    {
        return $this
            ->from('noreply@mg.yamycorp.com', 'YamyCorp System')
            ->to($this->email)
            ->subject('✅ Tu cuenta ha sido aprobada - YamyCorp')
            ->view('emails.access_approved')
            ->with(['email' => $this->email]);
    }
}
