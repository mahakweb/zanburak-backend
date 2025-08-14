<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AclRuleFileManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'path',
        'access'
    ];

    protected $table = 'acl_rules';




    public function user()
    {
       return $this->belongsTo(User::class);
    }
}
