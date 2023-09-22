<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Ban;

class BanResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $reason = $this->getFormattedReason();

        return [
            'id'         => $this->id,
            'banHash'    => $this->ban_hash,
			'smurfAccount' => $this->smurf_account,
            'reason'     => $reason['reason'],
            'original'   => $this->reason,
            'info'       => $reason['info'],
            'identifier' => $this->identifier,
            'expire'     => $this->expire,
            'expireAt'   => $this->expireAt,
            'timestamp'  => $this->timestamp,
            'issuer'     => $this->creator_name ?? null,
            'locked'     => $this->locked,
            'accuracy'   => $this->reason ? Ban::getAccuracy($this->reason) : false,
            'scheduled'  => $this->scheduled_unban ? date("m/d/Y", $this->scheduled_unban) : false
        ];
    }

}
