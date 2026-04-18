<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class SuspiciousEntitySpawn extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'suspicious_entity_spawns';

    /**
     * Whether to use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    
    /**
     * Get the player associated with the spawn.
     */
    public function player()
    {
        return $this->belongsTo(Player::class, 'license_identifier', 'license_identifier');
    }
}
