<?php

namespace App\Http\Resources;

use App\Helpers\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $keys = [];
        $canView = PermissionHelper::hasPermission(PermissionHelper::PERM_REALTY_VIEW);

        if ($canView && $this->resource->relationLoaded('access')) {
            foreach ($this->access as $access) {
                $keys['c_' . $access->character_id] = intval($access->access_level);
            }
        }

        return [
            'property_id'         => $this->property_id,
            'property_address'    => $this->property_address,
            'property_cost'       => $this->property_cost,
            'property_renter_cid' => $this->property_renter_cid,
            'property_income'     => $this->property_income,
            'property_last_pay'   => $this->property_last_pay,
			'keys'                => $keys,
        ];
    }

}
