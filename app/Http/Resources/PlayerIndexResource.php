<?php

namespace App\Http\Resources;

use App\Ban;
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
            'playerName'        => $this->getFilteredPlayerName(),
            'playTime'          => $this->playtime,
            'warnings'          => $this->warning_count,
            'isBanned'          => !!Ban::getBanForUser($this->license_identifier),
            'playtime'          => $this->playtime,
			'staffToggled'      => $this->isStaffToggled(),
			'staffHidden'       => $this->isStaffHidden(),
        ];
    }

}
