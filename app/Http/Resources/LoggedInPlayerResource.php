<?php

namespace App\Http\Resources;

use App\Helpers\GeneralHelper;
use App\Warning;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoggedInPlayerResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $drug = (isset($this->panel_drug_department) && $this->panel_drug_department) || ($this->license_identifier && GeneralHelper::isUserRoot($this->license_identifier));

        return [
            'id'                  => $this->user_id,
            'avatar'              => $this->avatar,
            'licenseIdentifier'   => $this->license_identifier,
            'playerName'          => $this->player_name,
            'safePlayerName'      => $this->getSafePlayerName(),
            'isTrusted'           => $this->is_trusted,
            'isDebugger'          => $this->isDebugger(),
            'isStaff'             => $this->isStaff(),
            'isSeniorStaff'       => $this->isSeniorStaff(),
            'isSuperAdmin'        => $this->isSuperAdmin(),
            'isRoot'              => $this->isRoot(),
            'panelDrugDepartment' => $drug,
            'tag'                 => $this->panel_tag,
            'staffToggled'        => $this->isStaffToggled(),
            'staffHidden'         => $this->isStaffHidden(),
        ];
    }

}
