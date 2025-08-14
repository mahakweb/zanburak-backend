<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $fillable = [
        'reportable_id',
        'reportable_type',
        'report',
        'status'
    ];


    public function user(){
        return $this->belongsTo(User::class);
    }
}
