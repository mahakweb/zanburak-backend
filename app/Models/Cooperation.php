<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cooperation extends Model
{
    protected $fillable = [
        'role',
        'name',
        'melli_code',
        'mobile',
        'email',
        'description',
        'links',
        'melli_card_image',
        'resume',
        'samples',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
