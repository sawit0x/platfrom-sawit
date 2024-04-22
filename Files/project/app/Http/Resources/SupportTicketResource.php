<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
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
            'id'         => $this->id,
            'ticket'     => $this->ticket_number,
            'subject'    => $this->subject,
            'message'    => $this->message,
            'attachment' => url('/') . '/assets/images/'.$this->attachment,
            'time'       => $this->created_at->diffForHumans(),
        ];
    }
}
