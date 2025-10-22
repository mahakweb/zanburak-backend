<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveCode extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_phone',
        'user_email',
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

    // unified helpers for phone or email
    public function generateCodeForContact(string $contact, int $expireMinutes = 3)
    {
        if ($code = $this->getAliveForContact($contact)) {
            return $code->code;
        }

        do {
            $code = mt_rand(100000, 999999);
        } while ($this->contactHasCode($contact, $code));

        $data = [
            'code' => $code,
            'expired_at' => now()->addMinutes($expireMinutes),
        ];

        if ($this->isEmail($contact)) {
            $data['user_email'] = $contact;
        } else {
            $data['user_phone'] = $contact;
        }

        $this->create($data);

        return $code;
    }

    public function verifyCodeForContact(string $contact, int $code): bool
    {
        $query = $this->where('code', $code)->where('expired_at', '>', now());
        if ($this->isEmail($contact)) {
            $query->where('user_email', $contact);
        } else {
            $query->where('user_phone', $contact);
        }
        return !! $query->first();
    }

    public function getAliveForContact(string $contact)
    {
        $query = $this->where('expired_at', '>', now());
        if ($this->isEmail($contact)) {
            $query->where('user_email', $contact);
        } else {
            $query->where('user_phone', $contact);
        }
        return $query->first();
    }

    private function contactHasCode(string $contact, int $code): bool
    {
        $query = $this->where('code', $code);
        if ($this->isEmail($contact)) {
            $query->where('user_email', $contact);
        } else {
            $query->where('user_phone', $contact);
        }
        return !! $query->first();
    }

    private function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
