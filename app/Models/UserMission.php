<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMission extends Model
{
    use HasFactory;
    protected $table = 'mission_user';
    protected $fillable = [
        'mission_id',
        'user_id',
        'progress',
        'completed_at',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function mission(){
        return $this->belongsTo(Mission::class);
    }

}
