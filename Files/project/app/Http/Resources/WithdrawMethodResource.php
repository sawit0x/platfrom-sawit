<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawMethodResource extends JsonResource
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
            'currency'      => $this->currency,
            'name'          => $this->name,
            'photo'         => url('/') . '/assets/images/'.$this->photo,
            'min_amount'    => $this->min_amount,
            'max_amount'    => $this->max_amount,
            'fixed'         => $this->fixed,
            'percentage'    => $this->percentage.'%',
        ];
    }
}
