<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Mail\ImapMailboxService;
use App\Services\Mail\MailOutboundService;
use Illuminate\Http\Request;

class MailInboxController extends Controller
{
    public function __construct(
        protected ImapMailboxService $mailbox,
        protected MailOutboundService $outbound,
    ) {}

    public function accounts()
    {
        return $this->success([
            'accounts' => $this->mailbox->listAccounts(),
        ]);
    }

    public function folders(string $account)
    {
        return $this->imap(fn () => [
            'folders' => $this->mailbox->listFolders($this->mailbox->resolveAccount($account)),
        ], $account);
    }

    public function stats(string $account)
    {
        return $this->imap(fn () => $this->mailbox->folderStats($this->mailbox->resolveAccount($account)), $account);
    }

    public function index(Request $request, string $account)
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
            'filter' => ['nullable', 'in:all,read,unread'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->imap(function () use ($account, $validated) {
            $result = $this->mailbox->listMessages(
                $this->mailbox->resolveAccount($account),
                $validated['folder'],
                $validated,
            );

            return $result;
        }, $account);
    }

    public function show(Request $request, string $account, int $uid)
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
            'mark_read' => ['nullable', 'boolean'],
        ]);

        return $this->imap(function () use ($account, $uid, $validated) {
            return $this->mailbox->getMessage(
                $this->mailbox->resolveAccount($account),
                $validated['folder'],
                $uid,
                (bool) ($validated['mark_read'] ?? true),
            );
        }, $account);
    }

    public function markRead(Request $request, string $account, int $uid)
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
        ]);

        return $this->imap(function () use ($account, $uid, $validated) {
            $this->mailbox->markRead(
                $this->mailbox->resolveAccount($account),
                $validated['folder'],
                $uid,
                true,
            );

            return ['marked' => 'read'];
        }, $account);
    }

    public function markUnread(Request $request, string $account, int $uid)
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
        ]);

        return $this->imap(function () use ($account, $uid, $validated) {
            $this->mailbox->markRead(
                $this->mailbox->resolveAccount($account),
                $validated['folder'],
                $uid,
                false,
            );

            return ['marked' => 'unread'];
        }, $account);
    }

    public function move(Request $request, string $account, int $uid)
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
            'target_folder' => ['required', 'string', 'max:255'],
        ]);

        return $this->imap(function () use ($account, $uid, $validated) {
            $this->mailbox->moveMessage(
                $this->mailbox->resolveAccount($account),
                $validated['folder'],
                $uid,
                $validated['target_folder'],
            );

            return ['moved' => true];
        }, $account);
    }

    public function destroy(Request $request, string $account, int $uid)
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
            'permanent' => ['nullable', 'boolean'],
        ]);

        return $this->imap(function () use ($account, $uid, $validated) {
            $this->mailbox->deleteMessage(
                $this->mailbox->resolveAccount($account),
                $validated['folder'],
                $uid,
                (bool) ($validated['permanent'] ?? false),
            );

            return ['deleted' => true];
        }, $account);
    }

    public function reply(Request $request, string $account, int $uid)
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:50000', 'required_without:body_html'],
            'body_html' => ['nullable', 'string', 'max:200000', 'required_without:body'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        return $this->imap(function () use ($account, $uid, $validated, $request) {
            $mailAccount = $this->mailbox->resolveAccount($account);
            $detail = $this->mailbox->getMessage($mailAccount, $validated['folder'], $uid, false);
            $message = $detail['message'];

            $replyToEmail = $message['is_outgoing']
                ? ($message['to_addresses'][0]['email'] ?? null)
                : $message['from_email'];

            if (! $replyToEmail) {
                throw new \RuntimeException('Could not determine reply recipient.');
            }

            $this->outbound->sendReply(
                account: $mailAccount,
                toEmail: $replyToEmail,
                toName: $message['is_outgoing']
                    ? ($message['to_addresses'][0]['name'] ?? null)
                    : $message['from_name'],
                subject: $message['subject'],
                body: $validated['body'] ?? '',
                bodyHtml: $validated['body_html'] ?? null,
                uploadedFiles: $request->file('attachments', []),
                inReplyTo: $message['message_id'],
                references: trim(($message['references'] ?? '').' '.($message['message_id'] ?? '')),
            );

            return [
                'sent' => true,
                'replied_by' => [
                    'id' => $request->user()->id,
                    'name' => trim($request->user()->first_name.' '.$request->user()->last_name) ?: $request->user()->username,
                ],
            ];
        }, $account);
    }

    public function compose(Request $request, string $account)
    {
        $validated = $request->validate([
            'to' => ['required', 'email', 'max:255'],
            'to_name' => ['nullable', 'string', 'max:255'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:50000', 'required_without:body_html'],
            'body_html' => ['nullable', 'string', 'max:200000', 'required_without:body'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        return $this->imap(function () use ($account, $validated, $request) {
            $this->outbound->sendCompose(
                account: $this->mailbox->resolveAccount($account),
                toEmail: $validated['to'],
                toName: $validated['to_name'] ?? null,
                subject: $validated['subject'],
                body: $validated['body'] ?? '',
                bodyHtml: $validated['body_html'] ?? null,
                cc: $validated['cc'] ?? [],
                uploadedFiles: $request->file('attachments', []),
            );

            return ['sent' => true];
        }, $account);
    }

    public function downloadAttachment(Request $request, string $account, int $uid, string $part)
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
        ]);

        try {
            $mailAccount = $this->mailbox->resolveAccount($account);
            $file = $this->mailbox->downloadAttachment(
                $mailAccount,
                $validated['folder'],
                $uid,
                $part,
            );

            return response($file['content'], 200, [
                'Content-Type' => $file['mime'],
                'Content-Disposition' => 'attachment; filename="'.addslashes($file['filename']).'"',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Mailbox not found.'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    protected function imap(callable $callback, string $accountKey)
    {
        try {
            $this->mailbox->resolveAccount($accountKey);

            return $this->success($callback());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Mailbox not found.'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            $message = $e->getMessage();
            if (stripos($message, 'connection failed') !== false) {
                $imap = config('mail-inbox.imap');
                $message = "اتصال IMAP به {$imap['host']}:{$imap['port']} ناموفق بود. "
                    .'روی لوکال معمولاً پورت ۹۹۳ بسته است — بک‌اند را روی سرور اصلی سایت اجرا کنید.';
            }

            return response()->json(['message' => $message], 500);
        }
    }

    protected function success(array $data)
    {
        return response()->json(array_merge(['message' => 'Success'], $data), 200);
    }
}
