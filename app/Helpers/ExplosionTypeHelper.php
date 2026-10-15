<?php

namespace App\Helpers;

class ExplosionTypeHelper
{
    public const TYPES = [
        -1 => 'DONTCARE',
        0 => 'GRENADE',
        1 => 'GRENADELAUNCHER',
        2 => 'STICKYBOMB',
        3 => 'MOLOTOV',
        4 => 'ROCKET',
        5 => 'TANKSHELL',
        6 => 'HI_OCTANE',
        7 => 'CAR',
        8 => 'PLANE',
        9 => 'PETROL_PUMP',
        10 => 'BIKE',
        11 => 'DIR_STEAM',
        12 => 'DIR_FLAME',
        13 => 'DIR_WATER_HYDRANT',
        14 => 'DIR_GAS_CANISTER',
        15 => 'BOAT',
        16 => 'SHIP_DESTROY',
        17 => 'TRUCK',
        18 => 'BULLET',
        19 => 'SMOKEGRENADELAUNCHER',
        20 => 'SMOKEGRENADE',
        21 => 'BZGAS',
        22 => 'FLARE',
        23 => 'GAS_CANISTER',
        24 => 'EXTINGUISHER',
        25 => 'PROGRAMMABLEAR',
        26 => 'TRAIN',
        27 => 'BARREL',
        28 => 'PROPANE',
        29 => 'BLIMP',
        30 => 'DIR_FLAME_EXPLODE',
        31 => 'TANKER',
        32 => 'PLANE_ROCKET',
        33 => 'VEHICLE_BULLET',
        34 => 'GAS_TANK',
        35 => 'BIRD_CRAP',
        36 => 'RAILGUN',
        37 => 'BLIMP2',
        38 => 'FIREWORK',
        39 => 'SNOWBALL',
        40 => 'PROXMINE',
        41 => 'VALKYRIE_CANNON',
        42 => 'AIR_DEFENSE',
        43 => 'PIPEBOMB',
        44 => 'VEHICLEMINE',
        45 => 'EXPLOSIVEAMMO',
        46 => 'APCSHELL',
        47 => 'BOMB_CLUSTER',
        48 => 'BOMB_GAS',
        49 => 'BOMB_INCENDIARY',
        50 => 'BOMB_STANDARD',
        51 => 'TORPEDO',
        52 => 'TORPEDO_UNDERWATER',
        53 => 'BOMBUSHKA_CANNON',
        54 => 'BOMB_CLUSTER_SECONDARY',
        55 => 'HUNTER_BARRAGE',
        56 => 'HUNTER_CANNON',
        57 => 'ROGUE_CANNON',
        58 => 'MINE_UNDERWATER',
        59 => 'ORBITAL_CANNON',
        60 => 'BOMB_STANDARD_WIDE',
        61 => 'EXPLOSIVEAMMO_SHOTGUN',
        62 => 'OPPRESSOR2_CANNON',
        63 => 'MORTAR_KINETIC',
        64 => 'VEHICLEMINE_KINETIC',
        65 => 'VEHICLEMINE_EMP',
        66 => 'VEHICLEMINE_SPIKE',
        67 => 'VEHICLEMINE_SLICK',
        68 => 'VEHICLEMINE_TAR',
        69 => 'SCRIPT_DRONE',
        70 => 'RAYGUN',
        71 => 'BURIEDMINE',
        72 => 'SCRIPT_MISSILE',
        73 => 'EXP_TAG_RCTANK_ROCKET',
        74 => 'EXP_TAG_BOMB_WATER',
        75 => 'EXP_TAG_BOMB_WATER_SECONDARY',
        76 => 'EXP_TAG_MINE_CNCSPIKE',
        77 => 'EXP_TAG_BZGAS_MK2',
        78 => 'EXP_TAG_FLASHGRENADE',
        79 => 'EXP_TAG_STUNGRENADE',
        80 => 'EXP_TAG_CNC_KINETICRAM',
        81 => 'EXP_TAG_SCRIPT_MISSILE_LARGE',
        82 => 'EXP_TAG_SUBMARINE_BIG',
        83 => 'EXP_TAG_EMPLAUNCHER_EMP',
        84 => 'EXP_TAG_RAILGUNXM3',
        85 => 'EXP_TAG_BALANCED_CANNONS',
    ];

    public const CATEGORIES = [
        'ignorable' => ['DIR_WATER_HYDRANT', 'SNOWBALL', 'BIRD_CRAP', 'FLARE'],
        'common' => ['PETROL_PUMP', 'CAR', 'TRUCK', 'FIREWORK', 'BIKE'],
        'dangerous' => [
            'BOMB_CLUSTER_SECONDARY',
            'BOMB_STANDARD_WIDE',
            'BOMB_GAS',
            'BOMB_INCENDIARY',
            'TANKSHELL',
            'PLANE_ROCKET',
            'VALKYRIE_CANNON',
            'OPPRESSOR2_CANNON',
            'APCSHELL',
            'EXP_TAG_RCTANK_ROCKET',
            'EXP_TAG_BALANCED_CANNONS',
            'PROGRAMMABLEAR',
            'BLIMP',
            'BLIMP2',
            'AIR_DEFENSE',
            'MINE_UNDERWATER',
            'ORBITAL_CANNON',
            'MORTAR_KINETIC',
            'VEHICLEMINE_KINETIC',
            'VEHICLEMINE_EMP',
            'VEHICLEMINE_SPIKE',
            'VEHICLEMINE_SLICK',
            'VEHICLEMINE_TAR',
            'SCRIPT_DRONE',
            'BURIEDMINE',
            'SCRIPT_MISSILE',
            'EXP_TAG_BOMB_WATER',
            'EXP_TAG_BOMB_WATER_SECONDARY',
            'EXP_TAG_MINE_CNCSPIKE',
            'EXP_TAG_BZGAS_MK2',
            'EXP_TAG_FLASHGRENADE',
            'EXP_TAG_STUNGRENADE',
            'EXP_TAG_CNC_KINETICRAM',
            'EXP_TAG_SCRIPT_MISSILE_LARGE',
        ],
    ];

    public static function categoryTypeIds(string $category): array
    {
        return array_keys(array_intersect(self::TYPES, self::CATEGORIES[$category]));
    }

    public static function frontendTypes(): array
    {
        $categories = [];

        foreach (self::CATEGORIES as $category => $names) {
            foreach ($names as $name) {
                $categories[$name] = $category;
            }
        }

        $types = [];

        foreach (self::TYPES as $id => $name) {
            $types[$id] = ['name' => $name, 'category' => $categories[$name] ?? 'other'];
        }

        return $types;
    }
}
