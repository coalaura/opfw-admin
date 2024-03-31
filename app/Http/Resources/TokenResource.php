<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $permissions = $this->getPermissions();

        return [
            'id'          => $this->token_id,
            'token'       => $this->token,
            'permissions' => $permissions,
            'note'        => $this->note,
            'requests'    => $this->total_requests,
            'lastRequest' => $this->last_request_timestamp,
        ];
    }

}
