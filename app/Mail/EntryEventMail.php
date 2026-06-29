<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EntryEventMail extends Mailable
{
    public function __construct(
        public readonly string $ruleName,
        public readonly string $uid,
        public readonly string $event,
        public readonly array  $fields,
    ) {}

    public function envelope(): Envelope
    {
        $label = match ($this->event) {
            'entry.create'    => 'New Entry',
            'entry.update'    => 'Entry Updated',
            'entry.publish'   => 'Entry Published',
            'entry.unpublish' => 'Entry Unpublished',
            'entry.delete'    => 'Entry Deleted',
            default           => ucwords(str_replace(['.', '_'], ' ', $this->event)),
        };

        return new Envelope(subject: "[Talos] {$label} — {$this->uid}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.entry-event',
            text: 'emails.entry-event-text',
        );
    }
}
