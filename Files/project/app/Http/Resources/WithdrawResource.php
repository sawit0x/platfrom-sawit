<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawResource extends JsonResource
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
            'id'             => $this->id,
            'user'           => $this->user,
            'payment_method' => $this->method,
            'amount'         => showNameAmount($this->amount),
            'fee'            => showNameAmount($this->fee),
            'status'         => $this->status,
            'details'        => $this->details,
        ];
    }
}
