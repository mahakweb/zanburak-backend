<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerUploadDaily extends Model
{
    protected $table = 'messenger_upload_daily';

    protected $fillable = [
        'user_id',
        'day',
        'bytes_used',
        'files_count',
    ];

    protected $casts = [
        'day' => 'date',
        'bytes_used' => 'integer',
        'files_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
