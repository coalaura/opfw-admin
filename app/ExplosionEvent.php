<?php

namespace App;

use App\Helpers\ExplosionTypeHelper;
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

    public function scopeCategory($query, string $category)
    {
        if ($category === '') {
            return $query;
        }

        $categories = match ($category) {
            'hide_ignorable' => ['ignorable'],
            'hide_common' => ['ignorable', 'common'],
            'other' => array_keys(ExplosionTypeHelper::CATEGORIES),
            default => [$category],
        };
        $types = [];

        foreach ($categories as $group) {
            $types = array_merge($types, ExplosionTypeHelper::categoryTypeIds($group));
        }

        if (in_array($category, ['hide_ignorable', 'hide_common', 'other'], true)) {
            // Unknown and NULL types must stay visible when excluding known categories.
            return $query->where(function ($query) use ($types) {
                $query->whereNotIn('explosion_type', $types)->orWhereNull('explosion_type');
            });
        }

        return $query->whereIn('explosion_type', $types);
    }
}
