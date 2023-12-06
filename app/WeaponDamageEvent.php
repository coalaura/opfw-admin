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

    private static $actionNames = null;

    const HitComponents = [
        // Confirmed (very sure):
        0  => "center (crotch/butt)",
        1  => "upper left leg",
        2  => "lower left leg",
        3  => "left foot",
        4  => "upper right leg",
        5  => "lower right leg",
        6  => "right foot",
        7  => "lower spine",
        8  => "stomach",
        9  => "upper chest",
        10 => "upper chest",
        11 => "left shoulder",
        12 => "upper left arm",
        13 => "lower left arm",
        14 => "left hand",
        15 => "right shoulder",
        16 => "upper right arm",
        17 => "lower right arm",
        18 => "right hand",
        19 => "neck",
        20 => "head",

        // Unknown/Unsure:
        21 => "unknown (21)",
        22 => "unknown (22)",
    ];

    const DamageTypes = [
        0  => "unknown (0)",
        1  => "no damage",
        2  => "melee",
        3  => "bullet",
        4  => "force ragdoll fall",
        5  => "explosive",
        6  => "fire",
        //7 => "",
        8  => "fall",
        //9 => "",
        10 => "electric",
        11 => "barbed wire",
        12 => "extinguisher",
        13 => "gas",
        14 => "water cannon",
    ];

    public static function getWeaponList(): array
    {
        $models = OPFWHelper::getModelsJSON(Server::getFirstServer() ?? '');

        if (!$models || !isset($models['weapons'])) {
            return [];
        }

        return $models['weapons'];
    }

    public static function getActionName($hash): string
    {
        if (!self::$actionNames) {
            $data = file_get_contents(__DIR__ . '/../helpers/action_names.json');

            self::$actionNames = json_decode($data, true);
        }

        if ($hash == 0) {
            return "none";
        }

        return self::$actionNames && isset(self::$actionNames[$hash]) ? self::$actionNames[$hash] : "hash_$hash";
    }

    public static function getHitComponent($component)
    {
        $component = intval($component);

        if (isset(self::HitComponents[$component])) {
            return self::HitComponents[$component];
        }

        return "undiscovered ($component)";
    }

    public static function getDamageType($type)
    {
        if (isset(self::DamageTypes[$type])) {
            return self::DamageTypes[$type];
        }

        return "unknown ($type)";
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

    public static function getDamaged(string $license, bool $includeNpcs)
    {
        $query = self::query()
            ->select(['license_identifier', 'timestamp_ms', 'hit_component', 'action_result_name', 'damage_type', 'weapon_type', 'distance', 'weapon_damage', 'hit_healths'])
            ->whereRaw("JSON_CONTAINS(hit_players, '\"" . $license . "\"', '$')");

        if (!$includeNpcs) {
            $query->where('is_parent_self', '=', '1');
        }

        return $query->orderByDesc('timestamp_ms')
            ->limit(500)
            ->get()->toArray();
    }

    public static function getDamageDealtTo(string $license, bool $includeNpcs)
    {
        $query = self::query()
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(hit_players, '$[0]')) as license_identifier, timestamp_ms, hit_component, action_result_name, damage_type, weapon_type, distance, weapon_damage, hit_healths")
            ->where('parent_global_id', '=', '0')
            ->where('license_identifier', $license);

        if (!$includeNpcs) {
            $query->where('is_parent_self', '=', '1');
        }

        return $query->orderByDesc('timestamp_ms')
            ->orderByDesc('timestamp_ms')
            ->limit(500)
            ->get()->toArray();
    }
}
