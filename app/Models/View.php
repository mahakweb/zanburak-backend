<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    use HasFactory;
    protected $fillable = ['ip_address', 'user_agent'];


    public function viewable()
    {
        return $this->morphTo();
    }

    
    public static function createFor($model)
    {
        $model->views()->create([
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }
}
