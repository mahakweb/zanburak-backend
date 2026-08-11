<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyException extends Model
{
    public const RULE_ALLOW = 'allow';

    public const RULE_DENY = 'deny';

    protected $fillable = [
        'user_id',
        'setting_key',
        'target_user_id',
        'rule',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
