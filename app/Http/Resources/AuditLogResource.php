<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
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
            'id'            => $this->id,
            'action'        => $this->action,
            'targetType'    => $this->target_type,
            'targetId'      => $this->target_id,
            'details'       => $this->details,
            'metadata'      => $this->metadata,
            'timestamp'     => $this->timestamp,
            'license'       => $this->license,
        ];
    }

}
