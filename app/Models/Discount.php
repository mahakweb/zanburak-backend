<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'percentage',
        'status',
        'expired_at',
    ];

    public function users(){
        return $this->belongsToMany(User::class);
    }


    public function courses(){
        return $this->belongsToMany(Course::class);
    }

    public function isActive(){
        return ($this->status == 1 && $this->expired_at > Carbon::now()) ? true : false;
    }


    public function isAvailableUser($id){
        if ( $this->users()->count() ){
            if ( ! in_array($id, $this->users->pluck('id')->toArray()) ){
                return false;
            }
            return true;
        }
        return true;
    }

}
