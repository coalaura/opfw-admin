<?php

namespace App\Http\Resources;

use App\YUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class YPostResource extends JsonResource
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
            'id'          => $this->id,
            'authorId'    => $this->authorId,
            'realUser'    => $this->realUser,
            'message'     => $this->message,
            'time'        => $this->time,
            'likes'       => $this->likes,

            // User data if used in a left join
            'username'    => $this->username ?? false,
            'is_verified' => $this->is_verified ?? false,
            'avatar_url'  => YUser::cleanupAvatar($this->avatar_url ?? false),
        ];
    }

}
