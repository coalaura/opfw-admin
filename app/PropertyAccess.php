<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyAccess extends Model
{
    protected $table = 'stocks_company_property_access';

    public $timestamps = false;

    protected $fillable = [
        'property_id',
        'character_id',
        'access_level',
    ];

    protected $casts = [
        'property_id'  => 'integer',
        'character_id' => 'integer',
        'access_level' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }
}
