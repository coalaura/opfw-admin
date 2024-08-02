<?php

namespace App;

use App\Helpers\OPFWHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'character_vehicles';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'vehicle_id';

    /**
     * Whether to use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'owner_cid',
        'garage_identifier',
        'garage_state',
        'garage_impound',
        'model_name',
        'plate',
        'vehicle_deleted',
        'deprecated_damage',
        'deprecated_modifications',
        'deprecated_fuel',
        'emergency_type',
        'last_garage_identifier',
        'oil_mileage_after',
        'mileage',
        'image_url',
        'deprecated_supporter'
    ];

    const PublicGarages = [
        1  => "Impound",
        2  => "Impound",
        3  => "Impound",
        4  => "Garage A",
        5  => "Garage B",
        6  => "Garage C",
        7  => "Garage D",
        8  => "Garage E",
        9  => "Garage F",
        10 => "Garage G",
        11 => "Garage H",
        12 => "Garage I",
        13 => "Impound",
        14 => "Garage J",
        15 => "Garage K",
        16 => "La Fuente Blanca",
        17 => "LSIA",
        18 => "MRPD",
        19 => "EMS",
        20 => "Luxury Autos",
        21 => "Garage L",
        22 => "Garage M",
        23 => "Garage N",
        24 => "Garage O",
        25 => "FIB",
        26 => "Garage P",
        27 => "Garage Q",
        28 => "Sandy Shores (Airfield)",
        29 => "DOC",
        30 => "Garage R",
        31 => "Garage S",
    ];

    /**
     * Get the character that owns this vehicle.
     *
     * @return BelongsTo
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'owner_cid');
    }

    public function getDisplayName(): ?string
    {
        $vehicles = OPFWHelper::getVehiclesJSON(Server::getFirstServer() ?? '');

        if (!$vehicles) {
            return null;
        }

        foreach($vehicles as $vehicle) {
            if ($vehicle['model'] === $this->model_name) {
                return $vehicle['label'];
            }
        }

        return null;
    }

    public function oilChangeMiles(): ?int
    {
        $milage     = $this->mileage;
        $oilMileage = $this->oil_mileage_after;

        if ($milage === null || $oilMileage === null) {
            return null;
        }

        return $oilMileage - $milage;
    }

    /**
     * Returns the garage name
     *
     * @return string
     */
    public function garage(): ?string
    {
        if (intval($this->garage_state) === 0) {
            return null;
        }

        $this->garage_identifier = trim($this->garage_identifier);

        if (is_numeric($this->garage_identifier)) {
            if (intval($this->emergency_type) === 1) {
                return 'PD Garage';
            } else if (intval($this->emergency_type) === 2) {
                return 'EMS Garage';
            }

            $garageId = intval($this->garage_identifier);

            if (isset(self::PublicGarages[$garageId])) {
                return self::PublicGarages[$garageId];
            }
        }

        return $this->garage_identifier;
    }

    /**
     * Get deprecated_modifications as a formatted array for the frontend
     *
     * @return array
     */
    public function getModifications(): array
    {
        $color = function (int $r, int $g, int $b): string {
            return sprintf("#%02x%02x%02x", $r, $g, $b);
        };
        $isColor = function (array $json, string $key): bool {
            return isset($json[$key]) && is_array($json[$key]) && !empty($json[$key]) && isset($json[$key]['r']) && isset($json[$key]['g']) && isset($json[$key]['b']);
        };

        $json    = json_decode($this->deprecated_modifications, true) ?? [];
        $default = '#ffffff';

        return [
            'xenon_headlights' => isset($json['modXenon']) && intval($json['modXenon']) === 1,
            'tire_smoke'       => $isColor($json, 'tireSmokeColor')
            ? $color($json['tireSmokeColor']['r'], $json['tireSmokeColor']['g'], $json['tireSmokeColor']['b'])
            : $default,
            'neon_enabled'     => isset($json['neonEnabled']) && sizeof($json['neonEnabled']) === 4 && $json['neonEnabled'][0] && $json['neonEnabled'][1] && $json['neonEnabled'][2] && $json['neonEnabled'][3],
            'engine'           => isset($json['modEngine']) && is_numeric($json['modEngine']) ? intval($json['modEngine']) + 1 : 0,
            'transmission'     => isset($json['modTransmission']) && is_numeric($json['modTransmission']) ? intval($json['modTransmission']) + 1 : 0,
            'breaks'           => isset($json['modBrakes']) && is_numeric($json['modBrakes']) ? intval($json['modBrakes']) + 1 : 0,
            'neon'             => $isColor($json, 'neonColor')
            ? $color($json['neonColor']['r'], $json['neonColor']['g'], $json['neonColor']['b'])
            : $default,
            'turbo'            => isset($json['modTurbo']) && intval($json['modTurbo']) === 1,
            'suspension'       => isset($json['modSuspension']) && is_numeric($json['modSuspension']) ? intval($json['modSuspension']) + 1 : 0,
            'armor'            => isset($json['modArmor']) && is_numeric($json['modArmor']) ? intval($json['modArmor']) + 1 : 0,
            'tint'             => isset($json['windowTint']) && is_numeric($json['windowTint']) ? intval($json['windowTint']) : 0,
            'plate_type'       => isset($json['plateIndex']) && is_numeric($json['plateIndex']) ? intval($json['plateIndex']) : 0,
            'horn'             => isset($json['modHorns']) && is_numeric($json['modHorns']) ? intval($json['modHorns']) : -1,
        ];
    }

    /**
     * Sets the vehicles deprecated_modifications based on an array from the frontend.
     * Returns the invalid key if there is one.
     *
     * @param array $mods
     * @return string|null
     */
    public function parseModifications(array $mods): ?string
    {
        $hornMap = self::getHornMap(true);
        $mods    = array_map(function ($m) {
            return is_numeric($m) ? intval($m) : $m;
        }, $mods);

        $validate = [
            'tire_smoke'       => !isset($mods['tire_smoke']) || !preg_match('/^#[0-9a-f]{6}$/mi', $mods['tire_smoke']),
            'neon'             => !isset($mods['neon']) || !preg_match('/^#[0-9a-f]{6}$/mi', $mods['neon']),
            'xenon_headlights' => !isset($mods['xenon_headlights']) || !is_bool($mods['xenon_headlights']),
            'neon_enabled'     => !isset($mods['neon_enabled']) || !is_bool($mods['neon_enabled']),
            'turbo'            => !isset($mods['turbo']) || !is_bool($mods['turbo']),
            'engine'           => !isset($mods['engine']) || !is_integer($mods['engine']) || $mods['engine'] < 0 || $mods['engine'] > 4,
            'transmission'     => !isset($mods['transmission']) || !is_integer($mods['transmission']) || $mods['transmission'] < 0 || $mods['transmission'] > 3,
            'breaks'           => !isset($mods['breaks']) || !is_integer($mods['breaks']) || $mods['breaks'] < 0 || $mods['breaks'] > 3,
            'suspension'       => !isset($mods['suspension']) || !is_integer($mods['suspension']) || $mods['suspension'] < 0 || $mods['suspension'] > 4,
            'armor'            => !isset($mods['armor']) || !is_integer($mods['armor']) || $mods['armor'] < 0 || $mods['armor'] > 5,
            'tint'             => !isset($mods['tint']) || !is_integer($mods['tint']) || $mods['tint'] < 0 || $mods['tint'] > 5,
            'plate_type'       => !isset($mods['plate_type']) || !is_integer($mods['plate_type']) || $mods['plate_type'] < 0 || $mods['plate_type'] > 12,
            'horn'             => !isset($mods['horn']) || !is_integer($mods['horn']) || !isset($hornMap[$mods['horn']]),
        ];

        foreach ($validate as $key => $invalid) {
            if ($invalid) {
                return $key;
            }
        }

        $color = function (string $hex): array {
            return [
                'r' => hexdec(substr($hex, 1, 2)),
                'g' => hexdec(substr($hex, 3, 2)),
                'b' => hexdec(substr($hex, 5, 2)),
            ];
        };
        $json = json_decode($this->deprecated_modifications, true) ?? [];

        $json['modXenon']        = $mods['xenon_headlights'] ? 1 : false;
        $json['tireSmokeColor']  = $color($mods['tire_smoke']);
        $json['neonEnabled']     = $mods['neon_enabled'] ? [1, 1, 1, 1] : [false, false, false, false];
        $json['modEngine']       = $mods['engine'] - 1;
        $json['modTransmission'] = $mods['transmission'] - 1;
        $json['modBrakes']       = $mods['breaks'] - 1;
        $json['neonColor']       = $color($mods['neon']);
        $json['modTurbo']        = $mods['turbo'] ? 1 : false;
        $json['modSuspension']   = $mods['suspension'] - 1;
        $json['modArmor']        = $mods['armor'] - 1;
        $json['windowTint']      = $mods['tint'];
        $json['plateIndex']      = $mods['plate_type'];
        $json['modHorns']        = $mods['horn'];

        $this->deprecated_modifications = json_encode($json);

        return null;
    }

    /**
     * Returns a map of all available vehicle horns
     *
     * @param bool $validationMap
     * @return array
     */
    public static function getHornMap(bool $validationMap = false): array
    {
        $hornMaps = json_decode(file_get_contents(__DIR__ . '/../helpers/vehicle-horns.json'), true);
        $horns    = [];

        if ($validationMap) {
            foreach ($hornMaps as $map) {
                foreach ($map as $key => $horn) {
                    $horns[$key] = $horn;
                }
            }
        } else {
            foreach ($hornMaps as $group => $map) {
                $horns[$group] = [];

                foreach ($map as $key => $horn) {
                    $horns[$group][] = [
                        'index' => $key,
                        'label' => $horn,
                    ];
                }
            }
        }

        return $horns;
    }

    public static function getVehiclePrices(): array
    {
        $vehicles = OPFWHelper::getVehiclesJSON(Server::getFirstServer() ?? '');

        $prices = [];

        if (isset($vehicles['pdm'])) {
            foreach ($vehicles['pdm'] as $vehicle) {
                $prices[$vehicle['modelName']] = intval($vehicle['price']);
            }
        }

        if (isset($vehicles['edm'])) {
            foreach ($vehicles['edm'] as $vehicle) {
                $prices[$vehicle['modelName']] = intval($vehicle['price']);
            }
        }

        return $prices;
    }

    public static function getTotalVehicleValue(?int $characterId = null): int
    {
        $prices = Vehicle::getVehiclePrices();

        $query = Vehicle::query()
            ->where('vehicle_deleted', '=', '0');

        if ($characterId) {
            $query->where('owner_cid', '=', $characterId);
        }

        $vehicles = $query->selectRaw('model_name, COUNT(vehicle_id) as `amount`')
            ->groupBy('model_name')
            ->get()->toArray();

        $total = 0;

        foreach ($vehicles as $vehicle) {
            $model = $vehicle['model_name'];

            if (isset($prices[$model])) {
                $total += $prices[$model] * intval($vehicle['amount']);
            }
        }

        return $total;
    }

}
