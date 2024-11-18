<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerRoleResource extends JsonResource
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

            'is_super_admin' => $this->is_super_admin,
            'is_senior_staff' => $this->is_senior_staff,
            'is_staff' => $this->is_staff,
            'is_trusted' => $this->is_trusted,
            'is_debugger' => $this->is_debugger,

            'isRoot' => $this->isRoot(),
        ];
    }

}
