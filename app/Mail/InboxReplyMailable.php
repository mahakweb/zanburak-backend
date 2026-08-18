<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class InboxReplyMailable extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<int, array{path: string, name: string, mime?: string}>  $files */
    public function __construct(
        public string $body,
        public string $subjectLine,
        public ?string $bodyHtml = null,
        public array $files = [],
        public ?string $inReplyTo = null,
        public ?string $references = null,
        public ?string $fromAddress = null,
        public ?string $fromName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress ?? config('mail.from.address'), $this->fromName ?? config('mail.from.name')),
            replyTo: [
                new Address($this->fromAddress ?? config('mail.from.address'), $this->fromName ?? config('mail.from.name')),
            ],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        if ($this->bodyHtml) {
            return new Content(
                html: 'mail.inbox-reply-html',
                text: 'mail.inbox-reply-text',
                with: [
                    'body' => $this->body,
                    'htmlBody' => $this->bodyHtml,
                ],
            );
        }

        return new Content(
            text: 'mail.inbox-reply-text',
            with: ['body' => $this->body],
        );
    }

    public function attachments(): array
    {
        return collect($this->files)->map(function (array $file) {
            return Attachment::fromPath($file['path'])
                ->as($file['name'])
                ->withMime($file['mime'] ?? 'application/octet-stream');
        })->all();
    }

    public function headers(): Headers
    {
        $text = [];

        if ($this->inReplyTo) {
            $messageId = str_contains($this->inReplyTo, '<') ? $this->inReplyTo : '<'.$this->inReplyTo.'>';
            $text['In-Reply-To'] = $messageId;
        }

        if ($this->references) {
            $text['References'] = $this->references;
        }

        return new Headers(text: $text);
    }
}
