<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

   protected $fillable = [
       'name',
       'username',
       'photo',
       'zip',
       'residency',
       'city',
       'address',
       'phone',
       'phone_code',
       'fax',
       'email',
       'password',
       'verification_link',
       'affilate_code',
       'is_provider',
       'twofa',
       'go',
       'details',
       'kyc_status',
       'kyc_info',
       'kyc_reject_reason'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function balanceTransfers(){
        return $this->hasMany(BalanceTransfer::class);
    }

    public function invests(){
        return $this->hasMany(Invest::class);
    }

    public function deposits(){
        return $this->hasMany(Deposit::class);
    }

    public function withdraws()
    {
        return $this->hasMany(Withdraw::class);
    }


    public function notifications()
    {
        return $this->hasMany('App\Models\Notification');
    }

    public function transactions()
    {
        return $this->hasMany('App\Models\Transaction','user_id');
    }
}
