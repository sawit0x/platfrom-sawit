<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'id'       => $this->id,
            'type'     => $this->type,
            'txnid'    => $this->txnid,
            'amount'   => showPrice($this->amount),
            'profit'    => $this->profit,
            'date'    => date('d M Y',strtotime($this->created_at))
        ];
    }
}
