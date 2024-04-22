<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'deposit_number',
        'amount',
        'coin_amount',
        'notify_id',
        'currency_id',
        'txnid',
        'method',
        'charge_id',
        'status',
    ];

    public function user(){
        return $this->belongsTo(User::class)->withDefault();
    }
}
