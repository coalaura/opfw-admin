<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PropertyAccess extends Model
{
    protected $table = 'stocks_company_property_access';

    public $timestamps = false;

    protected $fillable = [
        'property_id',
        'character_id',
        'access_level',
    ];
}
