<?php

namespace App\Services\Mail;

use App\Mail\InboxReplyMailable;
use App\Models\MailAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class MailOutboundService
{
    public function __construct(
        protected MailHtmlSanitizer $sanitizer,
    ) {}

    public function sendReply(
        MailAccount $account,
        string $toEmail,
        ?string $toName,
        string $subject,
        string $body,
        ?string $bodyHtml = null,
        array $uploadedFiles = [],
        ?string $inReplyTo = null,
        ?string $references = null,
    ): void {
        $this->send(
            account: $account,
            toEmail: $toEmail,
            toName: $toName,
            subject: $this->replySubject($subject),
            body: $body,
            bodyHtml: $bodyHtml,
            uploadedFiles: $uploadedFiles,
            inReplyTo: $inReplyTo,
            references: $references,
        );
    }

    public function sendCompose(
        MailAccount $account,
        string $toEmail,
        ?string $toName,
        string $subject,
        string $body,
        ?string $bodyHtml = null,
        array $cc = [],
        array $uploadedFiles = [],
    ): void {
        $mailable = $this->buildMailable(
            account: $account,
            subject: $subject,
            body: $body,
            bodyHtml: $bodyHtml,
            uploadedFiles: $uploadedFiles,
        );

        $mail = Mail::mailer('smtp')->to($toEmail, $toName);

        if (! empty($cc)) {
            $mail->cc($cc);
        }

        $mail->send($mailable);
    }

    protected function send(
        MailAccount $account,
        string $toEmail,
        ?string $toName,
        string $subject,
        string $body,
        ?string $bodyHtml = null,
        array $uploadedFiles = [],
        ?string $inReplyTo = null,
        ?string $references = null,
    ): void {
        $mailable = $this->buildMailable(
            account: $account,
            subject: $subject,
            body: $body,
            bodyHtml: $bodyHtml,
            uploadedFiles: $uploadedFiles,
            inReplyTo: $inReplyTo,
            references: $references,
        );

        Mail::mailer('smtp')->to($toEmail, $toName)->send($mailable);
    }

    protected function buildMailable(
        MailAccount $account,
        string $subject,
        string $body,
        ?string $bodyHtml = null,
        array $uploadedFiles = [],
        ?string $inReplyTo = null,
        ?string $references = null,
    ): InboxReplyMailable {
        $html = $bodyHtml ? $this->sanitizer->sanitize($bodyHtml) : null;
        $plain = $body ?: $this->sanitizer->toPlainText($html);

        return new InboxReplyMailable(
            body: $plain,
            subjectLine: $subject,
            bodyHtml: $html,
            files: $this->mapUploadedFiles($uploadedFiles),
            inReplyTo: $inReplyTo,
            references: $references,
            fromAddress: $account->address,
            fromName: $account->label,
        );
    }

    /** @param  UploadedFile[]  $uploadedFiles */
    protected function mapUploadedFiles(array $uploadedFiles): array
    {
        return collect($uploadedFiles)->map(function (UploadedFile $file) {
            return [
                'path' => $file->getRealPath(),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
            ];
        })->all();
    }

    protected function replySubject(?string $subject): string
    {
        $subject = trim((string) $subject);

        if ($subject === '') {
            return 'Re: (بدون موضوع)';
        }

        if (preg_match('/^re:\s/i', $subject)) {
            return $subject;
        }

        return 'Re: '.$subject;
    }
}
