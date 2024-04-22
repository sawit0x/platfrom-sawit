<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'iso2',
        'iso3',
        'phone_code',
        'postcode_required',
        'is_eu',
        'status',
    ];

    public function wiretransfers(){
        return $this->hasMany(WireTransferBank::class);
    }
}
