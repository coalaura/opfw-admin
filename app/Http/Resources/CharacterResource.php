<?php

namespace App\Http\Resources;

use App\Character;
use App\Helpers\GeneralHelper;
use App\Helpers\ServerAPI;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharacterResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $modelHash = $this->ped_model_hash ? intval($this->ped_model_hash) : null;
        $modelName = "";

        if ($modelHash) {
            $pedModels = ServerAPI::getPeds();
            $pedModel = $pedModels ? $pedModels[$modelHash] : null;

            $modelName = $pedModel ? $pedModel["ModelName"] : "";
        }

        return [
            'id'                         => $this->character_id,
            'licenseIdentifier'          => $this->license_identifier,
            'slot'                       => $this->character_slot,
            'gender'                     => $this->gender,
            'firstName'                  => $this->first_name,
            'lastName'                   => $this->last_name,
            'name'                       => $this->name,
            'bloodType'                  => $this->blood_type,
            'phoneNumber'                => $this->phone_number,
            'dateOfBirth'                => $this->date_of_birth,
            'isDead'                     => $this->is_dead,
            'cash'                       => $this->cash,
            'bank'                       => $this->bank,
            'money'                      => $this->money,
            'stocksBalance'              => $this->stocks_balance,
            'jobName'                    => $this->job_name,
            'departmentName'             => $this->department_name,
            'positionName'               => $this->position_name,
            'backstory'                  => $this->backstory,
            'vehicles'                   => VehicleResource::collection($this->vehicles),
            'properties'                 => PropertyResource::collection($this->properties),
            'accessProperties'           => PropertyResource::collection($this->accessProperties()),
            'characterDeleted'           => $this->character_deleted,
            'characterDeletionTimestamp' => $this->character_deletion_timestamp,
            'characterCreationTimestamp' => $this->character_creation_timestamp,
            'licenses'                   => $this->getLicenses(),
            'creationTime'               => $this->character_creation_time,
            'pedModelHash'               => $modelHash,
            'pedModelName'               => $modelName,
            'outfits'                    => Character::getOutfits($this->character_id, user()->isSeniorStaff()),
            'danny'                      => GeneralHelper::isDefaultDanny(intval($this->ped_model_hash), $this->ped_model_data),
            'mugshot'                    => $this->mugshot_url ?? null,
            'playtime'                   => $this->playtime,
            'playtime_2w'                => $this->getRecentPlaytime(2),
            'playtime_4w'                => $this->getRecentPlaytime(4),
            'lastLoaded'                 => $this->last_loaded,
            'coords'                     => $this->coords,
            'marriedTo'                  => $this->married_to,
            'emailAddress'               => $this->email_address,
        ];
    }
}
