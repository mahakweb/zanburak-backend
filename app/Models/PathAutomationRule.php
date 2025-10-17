<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathAutomationRule extends Model
{
    use HasFactory;

    protected $fillable = ['path_id', 'target', 'match_type', 'field', 'operator', 'value'];

    public function path()
    {
        return $this->belongsTo(Path::class);
    }
}


