<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLogin extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'device',
        'ip_address',
        'token_id',
        'login_type',
        'logged_in_at',
        'logged_out_at',
    ];

    protected $dates = [
        'logged_in_at',
        'logged_out_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function token()
    {
        return $this->belongsTo(\Laravel\Sanctum\PersonalAccessToken::class, 'token_id');
    }
}
