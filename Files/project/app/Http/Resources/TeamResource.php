<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'name'            => $this->name,
            'photo'           => url('/') . '/assets/images/'.$this->photo,
            'facebook_link'   => $this->fb_link,
            'twitter_link'    => $this->twitter_link,
            'instragram_link' => $this->instra_link,
            'linkedin_link'   => $this->linkedin_link,
            'youtube_link'    => $this->youtube_link,
            'created_at'      => $this->created_at
        ];
    }
}
