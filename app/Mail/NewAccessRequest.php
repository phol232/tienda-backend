<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewAccessRequest extends Mailable
{
    use Queueable, SerializesModels;

    public string  $email;
    public ?string $username;
    public ?string $messageText;
    public string  $approvalUrl;

    public function __construct(string $email, ?string $username, ?string $messageText, string $approvalUrl)
    {
        $this->email       = $email;
        $this->username    = $username;
        $this->messageText = $messageText;
        $this->approvalUrl = $approvalUrl;
    }

    public function build()
    {
        return $this
            ->from('admin@mg.yamycorp.com', 'YamyCorp Admin')
            ->to(env('ADMIN_EMAIL', 'admin@mg.yamycorp.com'))
            ->subject("🔔 Nueva solicitud de acceso: {$this->email}")
            ->view('emails.new_access_request')
            ->with([
                'email'       => $this->email,
                'username'    => $this->username,
                'messageText' => $this->messageText,
                'approvalUrl' => $this->approvalUrl,
            ]);
    }
}
