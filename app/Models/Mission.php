<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'title', 'category_id', 'icon', 'description', 'requirements', 'levels', 'expired_at'];

    protected $casts = [
        'id' => 'string', // change to string
    ];

    public function category()
    {
        return $this->belongsTo(MissionCategory::class, 'category_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'mission_user')->withPivot('progress', 'completed_at')->withTimestamps();
    }
}
