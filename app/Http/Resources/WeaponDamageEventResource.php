<?php

namespace App\Http\Resources;

use App\WeaponDamageEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeaponDamageEventResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $hitPlayer     = first($this->hit_players) ?? false;
        $hitHealth     = first($this->hit_healths) ?? false;
        $hitGlobalId   = first($this->hit_global_ids) ?? false;
        $hitEntityType = first($this->hit_entity_types) ?? false;

        return [
            'id'                => $this->id,
            'licenseIdentifier' => $this->license_identifier,
            'timestamp'         => $this->timestamp,
            'hitLicense'        => $hitPlayer,
            'hitHealth'         => $hitHealth,
            'distance'          => $this->distance,
            'hitGlobalId'       => $hitGlobalId,
            'hitEntityType'     => $hitEntityType,
            'hitComponent'      => WeaponDamageEvent::getHitComponent($this->hit_component),
            'damage'            => $this->weapon_damage,
            'weapon'            => WeaponDamageEvent::getDamageWeapon($this->weapon_type),
            'bonusDamage'       => $this->bonus_damage,
            'silenced'          => $this->silenced,
            'tireIndex'         => $this->tyre_index,
        ];
    }

}
