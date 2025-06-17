<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class YUserResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            "id"          => $this->id,
            "username"    => $this->username,
            "avatar_url"  => $this->getAvatar(),
            "creator_cid" => $this->creator_cid,
            "is_verified" => $this->is_verified,
        ];
    }

}
