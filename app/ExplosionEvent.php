<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExplosionEvent extends Model
{
    protected $table = 'explosion_events';

    public $timestamps = false;

    protected $casts = [
        'timestamp' => 'integer',
        'explosion_type' => 'integer',
        'distance' => 'double',
        'damage_scale' => 'double',
        'world_pos_x' => 'double',
        'world_pos_y' => 'double',
        'world_pos_z' => 'double',
        'explosion_pos_x' => 'double',
        'explosion_pos_y' => 'double',
        'explosion_pos_z' => 'double',
        'canceled' => 'boolean',
        'has_related_dummy' => 'boolean',
        'make_sound' => 'boolean',
        'no_damage' => 'boolean',
        'no_fx' => 'boolean',
        'in_air' => 'boolean',
        'entity_exploded' => 'boolean',
        'should_attach' => 'boolean',
        'attached_to_vehicle' => 'boolean',
        'detonating_other_explosive' => 'boolean',
        'has_projectile' => 'boolean',
    ];
}
