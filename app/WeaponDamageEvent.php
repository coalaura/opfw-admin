<?php

namespace App;

use App\Helpers\OPFWHelper;
use Illuminate\Database\Eloquent\Model;

class WeaponDamageEvent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'weapon_damage_events';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Whether to use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'hit_healths' => 'array',
    ];

    const HitComponents = [
        0  => "Buttocks",
        1  => "Left Thigh",
        2  => "Left Shin",
        3  => "Left Foot",
        4  => "Right Thigh",
        5  => "Right Shin",
        6  => "Right Foot",
        7  => "Spine0",
        8  => "Spine1",
        9  => "Spine2",
        10 => "Spine3",
        11 => "Left Clavicle",
        12 => "Left Upper Arm",
        13 => "Left Lower Arm",
        14 => "Left Hand",
        15 => "Right Clavicle",
        16 => "Right Upper Arm",
        17 => "Right Lower Arm",
        18 => "Right Hand",
        19 => "Neck",
        20 => "Head",
    ];

    public static function getWeaponList(): array
    {
        $models = OPFWHelper::getModelsJSON(Server::getFirstServer() ?? '');

        if (!$models || !isset($models['weapons'])) {
            return [];
        }

        return $models['weapons'];
    }

    public static function getHitComponent($component)
    {
        $component = intval($component);

        if (isset(self::HitComponents[$component])) {
            return self::HitComponents[$component];
        }

        return "unknown ($component)";
    }

    public static function getDamageWeapon($hash)
    {
        $list = self::getWeaponList();

        if (isset($list[$hash])) {
            return $list[$hash];
        }

        $signed = $hash - 4294967296;

        if (isset($list[$signed])) {
            return $list[$signed];
        }

        return "$hash/$signed";
    }

    public static function getWeaponHash($name)
    {
        $list = self::getWeaponList();

        foreach ($list as $hash => $weapon) {
            if ($weapon === $name) {
                return $hash;
            }
        }

        return null;
    }
}
