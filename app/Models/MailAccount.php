<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailAccount extends Model
{
    protected $fillable = [
        'key',
        'address',
        'label',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function credentials(): ?array
    {
        $config = config("mail-inbox.accounts.{$this->key}");

        if (! is_array($config)) {
            return null;
        }

        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        if (! $username || ! $password) {
            return null;
        }

        return $config;
    }

    public function isConfigured(): bool
    {
        return $this->credentials() !== null && ! empty(config('mail-inbox.imap.host'));
    }
}
