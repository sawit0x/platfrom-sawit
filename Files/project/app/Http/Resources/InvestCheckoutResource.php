<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvestCheckoutResource extends JsonResource
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
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'plan_id'         => $this->plan_id,
            'currency_sign'   => $this->currency->sign,
            'currency_name'   => $this->currency->name,
            'user_name'       => $this->user->name,
            'user_email'      => $this->user->email,
            'amount'          => $this->amount,
        ];
    }
}
