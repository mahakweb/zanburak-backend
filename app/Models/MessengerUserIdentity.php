<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerUserIdentity extends Model
{
    protected $table = 'messenger_user_identities';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'signing_public',
        'agreement_public',
        'encrypted_backup',
        'backup_salt',
        'backup_version',
    ];

    protected $casts = [
        'backup_version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toPublicMaterial(): array
    {
        return [
            'user_id' => (int) $this->user_id,
            'signing_public' => $this->signing_public,
            'agreement_public' => $this->agreement_public,
            'has_backup' => is_string($this->encrypted_backup) && $this->encrypted_backup !== '',
            'backup_version' => (int) $this->backup_version,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
