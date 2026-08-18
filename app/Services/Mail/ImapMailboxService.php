<?php

namespace App\Services\Mail;

use App\Models\MailAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\MessageCollection;

class ImapMailboxService
{
    public function __construct(
        protected MailHtmlSanitizer $htmlSanitizer,
        protected MailMimeDecoder $mimeDecoder,
    ) {}

    public function resolveAccount(string $key): MailAccount
    {
        return MailAccount::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function listAccounts(): array
    {
        return MailAccount::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn (MailAccount $account) => [
                'key' => $account->key,
                'address' => $account->address,
                'label' => $account->label,
                'from_name' => $account->displayFromName(),
                'is_configured' => $account->isConfigured(),
            ])
            ->values()
            ->all();
    }

    public function listFolders(MailAccount $account): array
    {
        return $this->withClient($account, function (Client $client) {
            $folders = $client->getFolders(false);
            $mapped = [];
            $typesFound = [];

            foreach ($folders as $folder) {
                /** @var Folder $folder */
                $type = $this->classifyFolder($folder->path);
                $status = $this->safeFolderStatus($folder);
                $typesFound[$type] = true;

                $mapped[] = [
                    'path' => $folder->path,
                    'name' => $folder->name,
                    'type' => $type,
                    'label' => config("mail-inbox.folder_labels.{$type}", $folder->name),
                    'unread' => (int) ($status['unseen'] ?? 0),
                    'total' => (int) ($status['messages'] ?? 0),
                ];
            }

            foreach (['inbox', 'sent', 'drafts', 'outbox', 'archive', 'spam', 'trash'] as $type) {
                if (isset($typesFound[$type])) {
                    continue;
                }

                $path = $this->resolveFolderPathByType($client, $type);
                if ($path) {
                    try {
                        $folder = $client->getFolderByPath($path);
                        $status = $this->safeFolderStatus($folder);
                        $mapped[] = [
                            'path' => $path,
                            'name' => $folder->name,
                            'type' => $type,
                            'label' => config("mail-inbox.folder_labels.{$type}", $folder->name),
                            'unread' => (int) ($status['unseen'] ?? 0),
                            'total' => (int) ($status['messages'] ?? 0),
                        ];
                    } catch (\Throwable) {
                        $mapped[] = [
                            'path' => $path,
                            'name' => $path,
                            'type' => $type,
                            'label' => config("mail-inbox.folder_labels.{$type}", $type),
                            'unread' => 0,
                            'total' => 0,
                        ];
                    }
                }
            }

            usort($mapped, fn ($a, $b) => $this->folderSortOrder($a['type']) <=> $this->folderSortOrder($b['type']));

            return $this->dedupeFoldersByType($mapped);
        });
    }

    public function downloadAttachment(MailAccount $account, string $folderPath, int $uid, string $part): array
    {
        return $this->withClient($account, function (Client $client) use ($folderPath, $uid, $part) {
            $folder = $client->getFolderByPath($folderPath);
            $message = $folder->query()->setFetchBody(true)->getMessageByUid($uid);
            $attachment = $this->findAttachment($message, $part);

            if (! $attachment) {
                throw new \RuntimeException('Attachment not found.');
            }

            $filename = $this->mimeDecoder->header($attachment->getName() ?: '') ?: 'attachment';
            $mime = $attachment->getContentType() ?: 'application/octet-stream';

            return [
                'filename' => $filename,
                'mime' => $mime,
                'content' => $attachment->getContent(),
            ];
        });
    }

    public function listMessages(MailAccount $account, string $folderPath, array $params = []): array
    {
        return $this->withClient($account, function (Client $client) use ($account, $folderPath, $params) {
            $folder = $client->getFolderByPath($folderPath);
            $query = $folder->query()
                ->all()
                ->setFetchBody(true)
                ->setFetchFlags(true)
                ->leaveUnread();

            $filter = $params['filter'] ?? 'all';
            if ($filter === 'unread') {
                $query->whereUnseen();
            } elseif ($filter === 'read') {
                $query->whereSeen();
            }

            if (! empty($params['search'])) {
                $query->whereText($params['search']);
            }

            $perPage = max(1, min(50, (int) ($params['perPage'] ?? 20)));
            $page = max(1, (int) ($params['page'] ?? 1));

            $messages = $query
                ->limit($perPage, $page)
                ->get()
                ->sortByDesc(fn (Message $message) => $this->messageTimestamp($message));

            $items = $messages->map(fn (Message $message) => $this->serializeListMessage($message, $folderPath, $account))->values()->all();

            $status = $this->safeFolderStatus($folder);
            $total = (int) ($status['messages'] ?? count($items));
            $lastPage = max(1, (int) ceil($total / $perPage));

            return [
                'messages' => $items,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                ],
                'folder' => [
                    'path' => $folderPath,
                    'type' => $this->classifyFolder($folderPath),
                    'unread' => (int) ($status['unseen'] ?? 0),
                ],
            ];
        });
    }

    public function getMessage(MailAccount $account, string $folderPath, int $uid, bool $markRead = true): array
    {
        return $this->withClient($account, function (Client $client) use ($account, $folderPath, $uid, $markRead) {
            $folder = $client->getFolderByPath($folderPath);
            $message = $folder->query()
                ->setFetchBody(true)
                ->setFetchFlags(true)
                ->getMessageByUid($uid);

            if ($markRead && ! $message->hasFlag('seen')) {
                $message->setFlag('Seen');
            }

            $detail = $this->serializeDetailMessage($message, $folderPath, $account);
            if ($markRead) {
                $detail['is_read'] = true;
            }
            $threadMessages = [$detail];

            $sentFolder = $this->resolveSentFolder($client);
            if ($sentFolder) {
                try {
                    $thread = $message->thread($sentFolder);
                    $serialized = $this->serializeThread($thread, $account);
                    if ($serialized !== []) {
                        $threadMessages = $serialized;
                    }
                } catch (\Throwable $e) {
                    Log::warning('IMAP thread fetch failed', ['error' => $e->getMessage()]);
                }
            }

            return [
                'message' => $detail,
                'thread' => $threadMessages,
            ];
        });
    }

    public function markRead(MailAccount $account, string $folderPath, int $uid, bool $read = true): void
    {
        $this->withClient($account, function (Client $client) use ($folderPath, $uid, $read) {
            $folder = $client->getFolderByPath($folderPath);
            $message = $folder->query()->getMessageByUid($uid);

            if ($read) {
                $message->setFlag('Seen');
            } else {
                $message->unsetFlag('Seen');
            }
        });
    }

    public function moveMessage(MailAccount $account, string $folderPath, int $uid, string $targetFolderPath): void
    {
        $this->withClient($account, function (Client $client) use ($folderPath, $uid, $targetFolderPath) {
            $folder = $client->getFolderByPath($folderPath);
            $message = $folder->query()->getMessageByUid($uid);
            $message->move($targetFolderPath);
        });
    }

    public function deleteMessage(MailAccount $account, string $folderPath, int $uid, bool $permanent = false): void
    {
        $this->withClient($account, function (Client $client) use ($account, $folderPath, $uid, $permanent) {
            $folder = $client->getFolderByPath($folderPath);
            $message = $folder->query()->getMessageByUid($uid);

            if ($permanent || $this->classifyFolder($folderPath) === 'trash') {
                $message->delete(true);
                return;
            }

            $trashPath = $this->resolveTrashFolderPath($client);
            if ($trashPath && $trashPath !== $folderPath) {
                $message->move($trashPath);
                return;
            }

            $message->delete(true);
        });
    }

    public function appendToSentFolder(MailAccount $account, string $rawMessage): void
    {
        $this->withClient($account, function (Client $client) use ($rawMessage) {
            $folder = $this->resolveSentFolder($client);
            if (! $folder) {
                return;
            }

            $folder->appendMessage($rawMessage, ['\\Seen']);
        });
    }

    public function folderStats(MailAccount $account): array
    {
        return $this->withClient($account, function (Client $client) {
            $stats = [];
            $totalUnread = 0;

            foreach ($client->getFolders(false) as $folder) {
                $type = $this->classifyFolder($folder->path);
                if (! in_array($type, ['inbox', 'sent', 'drafts', 'trash', 'spam', 'outbox', 'archive'], true)) {
                    continue;
                }

                $status = $this->safeFolderStatus($folder);
                $unread = (int) ($status['unseen'] ?? 0);
                $totalUnread += $unread;

                $stats[$type] = [
                    'path' => $folder->path,
                    'unread' => $unread,
                    'total' => (int) ($status['messages'] ?? 0),
                ];
            }

            return [
                'unread_total' => $totalUnread,
                'folders' => $stats,
            ];
        });
    }

    protected function withClient(MailAccount $account, callable $callback): mixed
    {
        if (! $account->isConfigured()) {
            throw new \RuntimeException('Mailbox credentials or IMAP host are not configured.');
        }

        $credentials = $account->credentials();
        $imap = config('mail-inbox.imap');

        $this->assertMailServerReachable($imap);

        $clientManager = new ClientManager();
        $client = $clientManager->make([
            'host' => $imap['host'],
            'port' => $imap['port'],
            'encryption' => $imap['encryption'],
            'validate_cert' => $imap['validate_cert'],
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'protocol' => $imap['protocol'] ?? 'imap',
            'options' => [
                'common_folders' => [
                    'sent' => $this->firstAlias('sent'),
                    'trash' => $this->firstAlias('trash'),
                ],
            ],
        ]);

        try {
            $client->connect();

            return $callback($client);
        } catch (\Webklex\PHPIMAP\Exceptions\AuthFailedException $e) {
            throw new \RuntimeException('ورود به صندوق ایمیل ناموفق بود. یوزرنیم باید کل آدرس ایمیل باشد و رمز با وب‌میل یکی باشد.');
        } catch (\Webklex\PHPIMAP\Exceptions\ConnectionFailedException $e) {
            throw new \RuntimeException($this->connectionHelpMessage($imap['host'], $imap['port'], $e->getMessage()));
        } finally {
            try {
                $client->disconnect();
            } catch (\Throwable $e) {
                Log::warning('IMAP disconnect failed', ['error' => $e->getMessage()]);
            }
        }
    }

    protected function serializeListMessage(Message $message, string $folderPath, MailAccount $account): array
    {
        $from = $message->getFrom()->first();

        return [
            'uid' => (int) $message->getUid(),
            'folder' => $folderPath,
            'message_id' => $this->attributeValue($message, 'message_id'),
            'from_name' => $this->mimeDecoder->header($from?->personal),
            'from_email' => $from?->mail ?? 'unknown@unknown',
            'subject' => $this->mimeDecoder->header($this->attributeValue($message, 'subject')) ?: '(بدون موضوع)',
            'preview' => $this->preview($message),
            'is_read' => $this->classifyFolder($folderPath) === 'sent' || $message->hasFlag('seen'),
            'is_flagged' => $message->hasFlag('flagged'),
            'has_attachments' => $message->getAttachments()->count() > 0,
            'is_outgoing' => $this->isOutgoing($message, $account),
            'received_at' => $this->messageTimestamp($message)?->toIso8601String(),
        ];
    }

    protected function serializeDetailMessage(Message $message, string $folderPath, MailAccount $account): array
    {
        $list = $this->serializeListMessage($message, $folderPath, $account);

        $bodyText = $this->mimeDecoder->body($message->getTextBody() ?: null);
        $bodyHtml = $this->mimeDecoder->body($message->getHTMLBody() ?: null);

        $security = $this->securityMeta($message);

        return array_merge($list, [
            'to_addresses' => $this->serializeAddresses($message->getTo()),
            'cc_addresses' => $this->serializeAddresses($message->getCc()),
            'bcc_addresses' => $this->serializeAddresses($message->getBcc()),
            'body_text' => $bodyText,
            'body_html' => $this->htmlSanitizer->sanitize($bodyHtml),
            'body_html_raw' => $bodyHtml,
            'attachments' => $this->serializeAttachments($message),
            'in_reply_to' => $this->attributeValue($message, 'in_reply_to'),
            'references' => $this->attributeValue($message, 'references'),
            'encryption' => $security['protocol'],
            'encrypted' => $security['encrypted'],
            'spf' => $security['spf'],
            'dkim' => $security['dkim'],
            'dmarc' => $security['dmarc'],
        ]);
    }

    protected function serializeThread(MessageCollection $thread, MailAccount $account): array
    {
        return $thread
            ->sortBy(fn (Message $message) => $this->messageTimestamp($message)?->timestamp ?? 0)
            ->map(function (Message $message) use ($account) {
                $folderPath = $message->getFolderPath();

                return $this->serializeDetailMessage($message, $folderPath, $account);
            })
            ->values()
            ->all();
    }

    protected function serializeAttachments(Message $message): array
    {
        return $message->getAttachments()->map(function (Attachment $attachment) {
            $name = $this->mimeDecoder->header($attachment->getName() ?: '') ?: 'attachment';
            $part = (string) ($attachment->getPartNumber() ?? $attachment->id ?? $attachment->hash);

            return [
                'part' => $part,
                'name' => $name,
                'size' => (int) ($attachment->getSize() ?? 0),
                'mime' => $attachment->getContentType() ?: 'application/octet-stream',
                'is_image' => str_starts_with((string) $attachment->getContentType(), 'image/'),
            ];
        })->values()->all();
    }

    protected function findAttachment(Message $message, string $part): ?Attachment
    {
        foreach ($message->getAttachments() as $attachment) {
            $candidates = array_filter([
                (string) $attachment->getPartNumber(),
                (string) ($attachment->id ?? ''),
                (string) ($attachment->hash ?? ''),
            ]);

            if (in_array($part, $candidates, true)) {
                return $attachment;
            }
        }

        return null;
    }

    protected function dedupeFoldersByType(array $folders): array
    {
        $seen = [];
        $result = [];

        foreach ($folders as $folder) {
            $key = $folder['type'] !== 'other' ? $folder['type'] : $folder['path'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $folder;
        }

        return $result;
    }

    protected function resolveFolderPathByType(Client $client, string $type): ?string
    {
        return $this->findFolderByAliases($client, $type)?->path
            ?? ($type === 'inbox' ? 'INBOX' : null);
    }

    protected function resolveSpamFolderPath(Client $client): ?string
    {
        return $this->resolveFolderPathByType($client, 'spam');
    }

    protected function serializeAddresses($collection): array
    {
        return collect($collection ?? [])->map(function ($address) {
            if (is_string($address)) {
                return [
                    'name' => null,
                    'email' => $address,
                ];
            }

            if (is_array($address)) {
                return [
                    'name' => $this->mimeDecoder->header($address['name'] ?? $address['personal'] ?? null) ?: null,
                    'email' => $address['email'] ?? $address['mail'] ?? null,
                ];
            }

            if (! is_object($address)) {
                return ['name' => null, 'email' => null];
            }

            return [
                'name' => $this->mimeDecoder->header($address->personal ?? null) ?: null,
                'email' => $address->mail ?? null,
            ];
        })->filter(fn ($row) => filled($row['email'] ?? null))->values()->all();
    }

    protected function isOutgoing(Message $message, MailAccount $account): bool
    {
        $from = $message->getFrom()->first();
        $fromEmail = strtolower((string) ($from?->mail ?? ''));

        return $fromEmail === strtolower($account->address)
            || $fromEmail === strtolower((string) ($account->credentials()['username'] ?? ''));
    }

    protected function preview(Message $message): string
    {
        return $this->mimeDecoder->preview(
            $message->getTextBody() ?: null,
            $message->getHTMLBody() ?: null,
        );
    }

    protected function messageTimestamp(Message $message): ?Carbon
    {
        $date = $message->getDate()?->first();
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse((string) $date);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function attributeValue(Message $message, string $name): ?string
    {
        $attribute = $message->{$name} ?? null;

        if ($attribute === null) {
            return null;
        }

        if (is_object($attribute) && method_exists($attribute, '__toString')) {
            $joined = trim((string) $attribute);
            if ($joined !== '' && $joined !== ',') {
                return $joined;
            }
        }

        if (is_object($attribute) && method_exists($attribute, 'first')) {
            $value = $attribute->first();

            if (is_object($value) && method_exists($value, '__toString')) {
                $value = (string) $value;
            }

            return is_string($value) ? trim($value) : (is_scalar($value) ? trim((string) $value) : null);
        }

        return is_string($attribute) ? trim($attribute) : null;
    }

    protected function securityMeta(Message $message): array
    {
        $encryption = strtolower((string) config('mail-inbox.imap.encryption', 'ssl'));
        $received = strtolower((string) ($this->attributeValue($message, 'received') ?? ''));
        $auth = strtolower((string) ($this->attributeValue($message, 'authentication_results') ?? ''));
        $encrypted = in_array($encryption, ['ssl', 'tls'], true)
            || str_contains($received, 'tls')
            || str_contains($received, 'using ssl');

        $status = static function (string $haystack, string $key): ?string {
            if (str_contains($haystack, $key.'=pass')) {
                return 'pass';
            }
            if (str_contains($haystack, $key.'=fail') || str_contains($haystack, $key.'=neutral')) {
                return 'fail';
            }

            return null;
        };

        return [
            'protocol' => $encrypted ? 'TLS' : 'بدون رمزنگاری',
            'encrypted' => $encrypted,
            'spf' => $status($auth, 'spf'),
            'dkim' => $status($auth, 'dkim'),
            'dmarc' => $status($auth, 'dmarc'),
        ];
    }

    protected function classifyFolder(string $path): string
    {
        $basename = $this->folderBasename($path);

        foreach (config('mail-inbox.folder_aliases', []) as $type => $aliases) {
            foreach ($aliases as $alias) {
                if (strcasecmp($basename, $this->folderBasename($alias)) === 0 || strcasecmp($path, $alias) === 0) {
                    return $type;
                }
            }
        }

        if (strcasecmp($basename, 'INBOX') === 0) {
            return 'inbox';
        }

        return 'other';
    }

    protected function folderBasename(string $path): string
    {
        $normalized = str_replace(['\\', '/'], '.', $path);
        $parts = array_filter(explode('.', $normalized));

        return $parts ? end($parts) : $path;
    }

    protected function folderSortOrder(string $type): int
    {
        return match ($type) {
            'inbox' => 1,
            'sent' => 2,
            'drafts' => 3,
            'outbox' => 4,
            'archive' => 5,
            'spam' => 6,
            'trash' => 7,
            default => 99,
        };
    }

    protected function safeFolderStatus(Folder $folder): array
    {
        try {
            return $folder->status();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function resolveSentFolder(Client $client): ?Folder
    {
        return $this->findFolderByAliases($client, 'sent');
    }

    protected function resolveTrashFolderPath(Client $client): ?string
    {
        return $this->findFolderByAliases($client, 'trash')?->path;
    }

    protected function findFolderByAliases(Client $client, string $type): ?Folder
    {
        foreach (config("mail-inbox.folder_aliases.{$type}", []) as $alias) {
            try {
                $folder = $client->getFolderByPath($alias);
                if ($folder) {
                    return $folder;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        foreach ($client->getFolders(false) as $folder) {
            if ($this->classifyFolder($folder->path) === $type) {
                return $folder;
            }
        }

        return null;
    }

    protected function firstAlias(string $type): string
    {
        $aliases = config("mail-inbox.folder_aliases.{$type}", []);

        return $aliases[0] ?? 'INBOX';
    }

    protected function assertMailServerReachable(array $imap): void
    {
        $host = $imap['host'] ?? '';
        $port = (int) ($imap['port'] ?? 993);
        $encryption = $imap['encryption'] ?? 'ssl';

        if ($host === '') {
            throw new \RuntimeException('آدرس سرور IMAP در تنظیمات مشخص نشده است (MAIL_INBOX_IMAP_HOST).');
        }

        $scheme = match ($encryption) {
            'ssl' => 'ssl',
            'tls' => 'tls',
            default => 'tcp',
        };

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "{$scheme}://{$host}:{$port}",
            $errno,
            $errstr,
            12,
            STREAM_CLIENT_CONNECT,
            stream_context_create([
                'ssl' => [
                    'verify_peer' => (bool) ($imap['validate_cert'] ?? true),
                    'verify_peer_name' => (bool) ($imap['validate_cert'] ?? true),
                ],
            ])
        );

        if ($socket === false) {
            throw new \RuntimeException($this->connectionHelpMessage($host, $port, $errstr ?: "errno {$errno}"));
        }

        fclose($socket);
    }

    protected function connectionHelpMessage(string $host, int $port, string $detail = ''): string
    {
        $base = "اتصال به سرور ایمیل ({$host}:{$port}) برقرار نشد.";

        $hints = [
            'اگر روی کامپیوتر شخصی (لوکال) تست می‌کنید: احتمالاً ISP یا فایروال ویندوز پورت ۹۹۳ را می‌بندد — بک‌اند را روی همان سرور سایت (production) اجرا کنید.',
            'در cPanel مطمئن شوید IMAP فعال است و رمز ایمیل درست است (MAIL_INBOX_DEFAULT_PASSWORD).',
            'یوزرنیم باید کل ایمیل باشد، مثلاً admin@zanburak.ir',
        ];

        return $base.' '.implode(' ', $hints).($detail ? " (جزئیات: {$detail})" : '');
    }
}
