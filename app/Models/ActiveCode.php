<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveCode extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_phone',
        'code',
        'expired_at'
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }


    public function scopeGenereteCode($query, $phone, $expireTime = 5){

        if($code = $this->getAliveCodeForUser($phone)){

            $code = $code->code;

        }else{

            do{

                $code = mt_rand(100000, 999999);

            }while($this->checkCodeIsUnique($phone, $code));

            $this->create([
                'user_phone' => $phone,
                'code' => $code,
                'expired_at' => now()->addMinute($expireTime),
            ]);

        }

        return $code;

    }






    private function checkCodeIsUnique($phone, int $code)
    {

        return !! $this->where('user_phone', $phone)->where('code', $code)->first();

    }


    public function getAliveCodeForUser($phone)
    {

        return $this->where('user_phone', $phone)->where('expired_at', '>', now())->first();

    }


    public function scopeVerifyCode($query, $phone, $code){

        return !! ActiveCode::where('user_phone', $phone)->where('code', $code)->where('expired_at', '>', now())->first();

    }

}
