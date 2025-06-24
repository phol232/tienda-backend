<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;
    public string $type;

    public function __construct(string $email, string $type = 'register')
    {
        $this->email = $email;
        $this->type = $type;
    }

    public function build()
    {
        $subject = $this->type === 'oauth'
            ? '📝 Solicitud de acceso enviada - YamyCorp'
            : '📝 Registro recibido - YamyCorp';

        return $this
            ->from('noreply@mg.yamycorp.com', 'YamyCorp System')
            ->to($this->email)
            ->subject($subject)
            ->view('emails.request_received')
            ->with([
                'email' => $this->email,
                'type' => $this->type
            ]);
    }
}
