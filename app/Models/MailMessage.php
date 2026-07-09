<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailMessage extends Model
{
    protected $fillable = [
        'mail_account_id',
        'imap_uid',
        'message_id',
        'in_reply_to',
        'references',
        'thread_id',
        'from_name',
        'from_email',
        'to_addresses',
        'cc_addresses',
        'subject',
        'body_text',
        'body_html',
        'is_read',
        'read_at',
        'has_attachments',
        'received_at',
        'replied_at',
        'replied_by_user_id',
    ];

    protected $casts = [
        'to_addresses' => 'array',
        'cc_addresses' => 'array',
        'is_read' => 'boolean',
        'has_attachments' => 'boolean',
        'read_at' => 'datetime',
        'received_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'mail_account_id');
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by_user_id');
    }

    public function markAsRead(): void
    {
        if ($this->is_read) {
            return;
        }

        $this->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();
    }

    public function markAsUnread(): void
    {
        $this->forceFill([
            'is_read' => false,
            'read_at' => null,
        ])->save();
    }

    public function preview(int $length = 120): string
    {
        $text = trim(strip_tags((string) ($this->body_text ?: $this->body_html)));

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length).'…';
    }
}
