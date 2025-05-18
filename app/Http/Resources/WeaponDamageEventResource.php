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
        return [
            'id'                => $this->id,
            'licenseIdentifier' => $this->license_identifier,
            'timestamp'         => $this->timestamp,
            'hitLicense'        => $this->hit_player ?? false,
            'hitHealth'         => $this->hit_health,
            'distance'          => $this->distance,
            'hitGlobalId'       => $this->hit_global_id,
            'hitEntityType'     => $this->hit_entity_type,
            'hitVehicleId'      => $this->vehicle_id,
            'hitComponent'      => WeaponDamageEvent::getHitComponent($this->hit_component),
            'damage'            => $this->weapon_damage,
            'weapon'            => WeaponDamageEvent::getDamageWeapon($this->weapon_type),
            'bonusDamage'       => $this->bonus_damage,
            'silenced'          => $this->silenced,
            'tireIndex'         => $this->tyre_index,
            'suspensionIndex'   => $this->suspension_index,
            'flags'             => $this->damage_flags,
        ];
    }

}
