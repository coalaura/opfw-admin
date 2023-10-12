<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PanelLogResource extends JsonResource
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
            'id'        => $this->id,
            'action'    => $this->action,
            'log'       => $this->log,
            'timestamp' => $this->timestamp,
        ];
    }

}
