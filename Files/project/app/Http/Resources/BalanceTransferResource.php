<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'transaction_no'   => $this->transaction_no,
            'receiver_email'   => $this->userEmail($this->receiver_id),
            'amount'           => showNameAmount($this->amount),
            'cost'             => showNameAmount($this->cost),
            'status'           =>  $this->status == 1 ? 'Succeed' : 'Pending',
        ];
    }

    public function userEmail($receiver_id){
        $user = User::whereId($receiver_id)->first();
        if($user){
            return $user->email;
        }
        return ' ';
    }
}
