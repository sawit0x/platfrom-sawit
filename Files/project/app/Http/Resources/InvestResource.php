<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvestResource extends JsonResource
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
            'transaction_no' => $this->transaction_no,
            'method'         => $this->method,
            'plan'           => $this->plan->title,
            'profit_amount'  => showNameAmount($this->profit),
            'status'         => ($this->status == 0) ? 'Pending' : (($this->status == 1) ? "Running" : "Completed"),
            'next_profit'    => $this->profit_time,
        ];
    }
}
