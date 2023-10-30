<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MoneyLogResource extends JsonResource
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
            'type'              => $this->type,
            'licenseIdentifier' => $this->license_identifier,
            'characterId'       => $this->character_id,
            'amount'            => $this->amount,
            'balanceAfter'      => $this->balance_after,
            'balanceBefore'     => $this->balance_after - $this->amount,
            'details'           => $this->details,
            'timestamp'         => $this->timestamp,
            'playerName'        => $this->player_name,
            'characterName'     => $this->character_name,
        ];
    }

}
