<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class MoneyRequestResource extends JsonResource
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
            'id'            => $this->id,
            'senderName'    => $this->userName($this->user_id),
            'amount'        => showNameAmount($this->amount),
            'cost'          => showNameAmount($this->cost),
            'amount_to_get' => showNameAmount($this->amount - $this->cost),
            'receiverName'  => $this->receiver_name,
            'status'        =>  $this->status == 1 ? 'Succeed' : 'Pending',
            'date'          => date('d M Y',strtotime($this->created_at))
        ];
    }

    public function userName($id){
        $user = User::whereId($id)->first();
        if($user){
            return $user->name;
        }
        return ' ';
    }
}
