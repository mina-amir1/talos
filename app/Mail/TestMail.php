<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Talos CMS — SMTP connection verified');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test',
            text:  'emails.test-text',
        );
    }
}
