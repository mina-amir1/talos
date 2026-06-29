<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InviteUserMail extends Mailable
{
    public function __construct(
        public readonly string $name,
        public readonly string $inviteUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "You've been invited to Talos CMS");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invite-user',
            text: 'emails.invite-user-text',
        );
    }
}
