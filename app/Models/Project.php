<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'type',
        'sample',
        'description',
        'deadline',
        'min_price',
        'max_price',
        'attach_file',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
