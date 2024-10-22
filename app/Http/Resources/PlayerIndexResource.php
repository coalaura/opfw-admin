<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerIndexResource extends JsonResource
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
            'licenseIdentifier' => $this->license_identifier,
            'playerName'        => $this->getSafePlayerName(),
            'playTime'          => $this->playtime,
            'isBanned'          => !!$this->ban_hash,
            'playtime'          => $this->playtime,
			'staffToggled'      => $this->isStaffToggled(),
			'staffHidden'       => $this->isStaffHidden(),
        ];
    }

}
