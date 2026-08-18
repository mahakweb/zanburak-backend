<?php

namespace App\Services\Mail;

use App\Mail\InboxReplyMailable;
use App\Models\MailAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

class MailOutboundService
{
    public function __construct(
        protected MailHtmlSanitizer $sanitizer,
        protected ImapMailboxService $mailbox,
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
        $this->send(
            account: $account,
            toEmail: $toEmail,
            toName: $toName,
            subject: $subject,
            body: $body,
            bodyHtml: $bodyHtml,
            cc: $cc,
            uploadedFiles: $uploadedFiles,
        );
    }

    protected function send(
        MailAccount $account,
        string $toEmail,
        ?string $toName,
        string $subject,
        string $body,
        ?string $bodyHtml = null,
        array $cc = [],
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

        $rawRfc822 = null;
        $mailable->withSymfonyMessage(function (Email $message) use (&$rawRfc822) {
            $rawRfc822 = $message->toString();
        });

        $mail = $this->accountMailer($account)->to($toEmail, $toName);

        if (! empty($cc)) {
            $mail->cc($cc);
        }

        $mail->send($mailable);

        $this->storeInSentFolder($account, $rawRfc822);
    }

    protected function accountMailer(MailAccount $account)
    {
        $credentials = $account->credentials();
        if (! $credentials) {
            throw new \RuntimeException('اطلاعات ورود صندوق ایمیل برای ارسال تنظیم نشده است.');
        }

        $smtp = config('mail-inbox.smtp');

        return Mail::build([
            'transport' => 'smtp',
            'host' => $smtp['host'],
            'port' => $smtp['port'],
            'encryption' => $smtp['encryption'],
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'timeout' => 30,
        ]);
    }

    protected function storeInSentFolder(MailAccount $account, ?string $rawRfc822): void
    {
        if (! $rawRfc822) {
            return;
        }

        try {
            $this->mailbox->appendToSentFolder($account, $rawRfc822);
        } catch (\Throwable $e) {
            Log::warning('Failed to copy outbound mail into IMAP Sent folder', [
                'account' => $account->key,
                'error' => $e->getMessage(),
            ]);
        }
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
        $html = $bodyHtml ? $this->sanitizer->wrapOutgoing($bodyHtml) : null;
        $plain = $body ?: $this->sanitizer->toPlainText($html);

        return new InboxReplyMailable(
            body: $plain,
            subjectLine: $subject,
            bodyHtml: $html,
            files: $this->mapUploadedFiles($uploadedFiles),
            inReplyTo: $inReplyTo,
            references: $references,
            fromAddress: $account->address,
            fromName: $account->displayFromName(),
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
