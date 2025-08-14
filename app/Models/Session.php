<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class Session extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
        'created_at'
    ];

    protected $casts = [
        'id' => 'string',
    ];



    public function user(){
        return $this->belongsTo(User::class);
    }


    public function platform(){
        $agent = new Agent();
        $agent->setUserAgent($this->user_agent);
        return Str::lower($agent->platform());

    }

    public function deviceInfo(){
        $agent = new Agent();
        $agent->setUserAgent($this->user_agent);
        $browser = $agent->browser();
        $platform = $agent->platform();
        return $platform.'('. $agent->version($platform).') - مرورگر('.$browser.')';
    }

    // public function isCurrentSession(){
    //     return ($_SERVER['HTTP_USER_AGENT'] == $this->user_agent && $_SERVER['REMOTE_ADDR'] == $this->ip_address) ? true : false;
    // }
}
