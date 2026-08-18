<?php

namespace App\Services\Mail;

use App\Models\MailAccount;
use App\Models\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

class ImapSyncService
{
    public function syncAll(): array
    {
        $results = [];

        foreach (MailAccount::where('is_active', true)->get() as $account) {
            $results[$account->key] = $this->syncAccount($account);
        }

        return $results;
    }

    public function syncAccount(MailAccount $account): array
    {
        if (! $account->isConfigured()) {
            return [
                'synced' => 0,
                'skipped' => true,
                'message' => 'Mailbox credentials or IMAP host are not configured.',
            ];
        }

        $credentials = $account->credentials();
        $imap = config('mail-inbox.imap');

        try {
            $clientManager = new ClientManager();
            $client = $clientManager->make([
                'host' => $imap['host'],
                'port' => $imap['port'],
                'encryption' => $imap['encryption'],
                'validate_cert' => $imap['validate_cert'],
                'username' => $credentials['username'],
                'password' => $credentials['password'],
                'protocol' => $imap['protocol'] ?? 'imap',
            ]);

            $client->connect();

            $folder = $client->getFolder($imap['folder'] ?? 'INBOX');
            $query = $folder->query()->all()->setFetchBody(true)->setFetchFlags(true);

            if ($account->last_synced_at) {
                $query->whereSince($account->last_synced_at->copy()->subDay()->format('d-M-Y'));
            } else {
                $query->limit(50, 1);
            }

            $messages = $query->get();
            $synced = 0;
            $maxUid = $account->last_synced_uid;

            foreach ($messages as $message) {
                /** @var Message $message */
                $uid = (int) $message->getUid();
                if ($uid <= 0) {
                    continue;
                }

                $this->storeMessage($account, $message);
                $synced++;
                $maxUid = max($maxUid, $uid);
            }

            $account->forceFill([
                'last_synced_uid' => $maxUid,
                'last_synced_at' => now(),
            ])->save();

            $client->disconnect();

            return [
                'synced' => $synced,
                'skipped' => false,
            ];
        } catch (\Throwable $e) {
            Log::error('Mail inbox sync failed', [
                'account' => $account->key,
                'error' => $e->getMessage(),
            ]);

            return [
                'synced' => 0,
                'skipped' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function storeMessage(MailAccount $account, Message $message): MailMessage
    {
        $from = $message->getFrom()->first();
        $messageId = $this->attributeValue($message, 'message_id');
        $inReplyTo = $this->attributeValue($message, 'in_reply_to');
        $references = $this->attributeValue($message, 'references');

        $toAddresses = $message->getTo()->map(function ($address) {
            return [
                'name' => $address->personal ?? null,
                'email' => $address->mail ?? null,
            ];
        })->values()->all();

        $ccAddresses = $message->getCc()->map(function ($address) {
            return [
                'name' => $address->personal ?? null,
                'email' => $address->mail ?? null,
            ];
        })->values()->all();

        $receivedAt = $this->parseDate($message->getDate()?->first());

        $record = MailMessage::updateOrCreate(
            [
                'mail_account_id' => $account->id,
                'imap_uid' => (int) $message->getUid(),
            ],
            [
                'message_id' => $messageId,
                'in_reply_to' => $inReplyTo,
                'references' => $references,
                'thread_id' => $messageId ?: ($inReplyTo ?: 'uid-'.$message->getUid()),
                'from_name' => $from?->personal,
                'from_email' => $from?->mail ?? 'unknown@unknown',
                'to_addresses' => $toAddresses,
                'cc_addresses' => $ccAddresses,
                'subject' => $this->attributeValue($message, 'subject') ?: '(بدون موضوع)',
                'body_text' => $message->getTextBody() ?: null,
                'body_html' => $message->getHTMLBody() ?: null,
                'has_attachments' => $message->getAttachments()->count() > 0,
                'received_at' => $receivedAt,
            ]
        );

        if ($message->getFlags()->contains('seen') && ! $record->is_read) {
            $record->markAsRead();
        }

        return $record;
    }

    protected function attributeValue(Message $message, string $name): ?string
    {
        $attribute = $message->{$name} ?? null;

        if ($attribute === null) {
            return null;
        }

        if (is_object($attribute) && method_exists($attribute, 'first')) {
            $value = $attribute->first();

            return is_string($value) ? trim($value) : null;
        }

        return is_string($attribute) ? trim($attribute) : null;
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
