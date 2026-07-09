<?php

$defaultPassword = env('MAIL_INBOX_DEFAULT_PASSWORD');

$mailbox = static function (string $address, string $label, ?string $envPasswordKey = null) use ($defaultPassword): array {
    $local = strtolower(strtok($address, '@'));

    return [
        'address' => env('MAIL_INBOX_'.strtoupper($local).'_ADDRESS', $address),
        'label' => $label,
        'username' => env('MAIL_INBOX_'.strtoupper($local).'_USERNAME', $address),
        'password' => $envPasswordKey
            ? env($envPasswordKey, $defaultPassword)
            : $defaultPassword,
    ];
};

return [

    'imap' => [
        'host' => env('MAIL_INBOX_IMAP_HOST', 'mail.zanburak.ir'),
        'port' => (int) env('MAIL_INBOX_IMAP_PORT', 993),
        'encryption' => env('MAIL_INBOX_IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => env('MAIL_INBOX_IMAP_VALIDATE_CERT', true),
    ],

    'accounts' => [
        'admin' => $mailbox('admin@zanburak.ir', 'مدیریت'),
        'support' => $mailbox('support@zanburak.ir', 'پشتیبانی'),
        'info' => $mailbox('info@zanburak.ir', 'اطلاعات'),
        'contact' => $mailbox('contact@zanburak.ir', 'تماس با ما'),
        'noreply' => $mailbox('noreply@zanburak.ir', 'بدون پاسخ'),
        'security' => $mailbox('security@zanburak.ir', 'امنیت'),
        'teacher' => $mailbox('teacher@zanburak.ir', 'مدرسین'),
    ],

    'folder_aliases' => [
        'inbox' => ['INBOX', 'Inbox'],
        'sent' => ['Sent', 'Sent Items', 'Sent Messages', 'Sent Mail', 'INBOX.Sent', 'INBOX/Sent', 'INBOX.Sent Items', '.Sent', '[Gmail]/Sent Mail'],
        'drafts' => ['Drafts', 'Draft', 'INBOX.Drafts', 'INBOX/Drafts', 'INBOX.Draft', '.Drafts', '[Gmail]/Drafts'],
        'trash' => ['Trash', 'Deleted', 'Deleted Items', 'Bin', 'INBOX.Trash', 'INBOX/Trash', 'INBOX.Deleted', '.Trash', '[Gmail]/Trash'],
        'spam' => ['Spam', 'Junk', 'Junk E-mail', 'Junk Email', 'INBOX.Spam', 'INBOX/Spam', 'INBOX.Junk', '.Spam', '[Gmail]/Spam'],
        'outbox' => ['Outbox', 'INBOX.Outbox', 'INBOX/Outbox', '.Outbox'],
    ],

    'folder_labels' => [
        'inbox' => 'دریافتی',
        'sent' => 'ارسال‌شده',
        'drafts' => 'پیش‌نویس',
        'trash' => 'حذف‌شده',
        'spam' => 'هرزنامه',
        'outbox' => 'در حال ارسال',
        'other' => 'سایر',
    ],

];
