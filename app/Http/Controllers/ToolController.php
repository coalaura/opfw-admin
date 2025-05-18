<?php
namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\WeaponDamageEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ToolController extends Controller
{
    /**
     * Config generator.
     *
     * @return Response
     */
    public function config()
    {
        $config = ServerAPI::getConfig();

        $parameters = [];

        foreach ($config as $key => $value) {
            $type = null;

            if (is_array($value)) {
                $type = $value['type'] ?? $value['Type'];
            }

            if (! $type || ! (Str::startsWith($type, 'array') || Str::startsWith($type, 'map'))) {
                continue;
            }

            $parameters[] = $key;
        }

        sort($parameters);

        return Inertia::render('Tools/Config', [
            'parameters' => $parameters,
        ]);
    }

    /**
     * Vehicles search.
     *
     * @return Response
     */
    public function vehicles(): Response
    {
        $vehicles = ServerAPI::getVehiclesTxt();

        return Inertia::render('Tools/Vehicles', [
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Weapons search.
     *
     * @return Response
     */
    public function weapons(): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $weapons = WeaponDamageEvent::getWeaponListFlat();

        // Collect usages
        $usages = CacheHelper::read('weapon_usages');

        if (! $usages) {
            $data = DB::table('inventories')
                ->select(DB::raw('COUNT(item_name) as count'), 'item_name')
                ->where('inventory_name', 'like', 'character-%')
                ->where('item_name', 'like', 'weapon_%')
                ->where(function ($query) {
                    $query->whereNull(DB::raw("JSON_EXTRACT(item_metadata, '$.degradesAt')"))
                        ->orWhere(DB::raw("JSON_EXTRACT(item_metadata, '$.degradesAt')"), '>', time());
                })
                ->groupBy('item_name')
                ->get();

            $usage = [];

            foreach ($data as $item) {
                $weapon = $item->item_name;
                $count  = $item->count;

                $type = WeaponDamageEvent::getWeaponType($weapon);

                if (! $type || $type === "throwable" || $type === "misc" || $type === "melee") {
                    continue;
                }

                $usage[] = [
                    'weapon' => $weapon,
                    'count'  => $count,
                    'type'   => $type,
                ];
            }

            // Sort by type asc, then by count desc then by name asc
            usort($usage, function ($a, $b) {
                if ($a['type'] == $b['type']) {
                    if ($a['count'] == $b['count']) {
                        return strcmp($a['weapon'], $b['weapon']);
                    }

                    return $b['count'] - $a['count'];
                }

                return strcmp($a['type'], $b['type']);
            });

            $categories  = [];
            $usageLabels = [];
            $usageData   = [];

            foreach ($usage as $item) {
                $name = $item['weapon'];
                $type = $item['type'];

                $categories[$name] = $type;
                $usageLabels[]     = sprintf('%s (%s)', $name, $type);
                $usageData[]       = $item['count'];
            }

            $usages = [
                'data'       => [
                    $usageData,
                ],
                'labels'     => $usageLabels,
                'names'      => ['weapons.items_in_use'],
                'categories' => $categories,
            ];

            CacheHelper::write('weapon_usages', $usages, CacheHelper::HOUR * 2);
        }

        return Inertia::render('Tools/Weapons', [
            'weapons' => $weapons,
            'usages'  => $usages,
        ]);
    }

    /**
     * Weapons search API.
     *
     * @param int $hash
     */
    public function searchWeapons(int $hash)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $weapons = WeaponDamageEvent::getWeaponListFlat();

        if (! isset($weapons[$hash])) {
            abort(404);
        }

        $unsigned = $hash + 4294967296;

        $data = WeaponDamageEvent::query()
            ->select([DB::raw('COUNT(DISTINCT license_identifier) as count'), 'weapon_damage', 'ban_hash'])
            ->leftJoin('user_bans', 'identifier', '=', 'license_identifier')
            ->where('weapon_damage_events.timestamp', '>', time() - 60 * 60 * 24 * 120 * 1000)
            ->where('is_parent_self', '=', '1')
            ->whereIn('weapon_type', [$hash, $unsigned])
            ->whereNotNull('hit_player')
            ->where('hit_player', '!=', '')
            ->groupBy(['weapon_damage', 'ban_hash'])
            ->get()->toArray();

        if (empty($data)) {
            return $this->json(false, null, 'No data');
        }

        $rawData = WeaponDamageEvent::query()
            ->select([DB::raw('COUNT(id) as count'), 'weapon_damage'])
            ->where('timestamp', '>', time() - 60 * 60 * 24 * 120 * 1000)
            ->where('is_parent_self', '=', '1')
            ->whereIn('weapon_type', [$hash, $unsigned])
            ->whereNotNull('hit_player')
            ->where('hit_player', '!=', '')
            ->groupBy('weapon_damage')
            ->get()->toArray();

        $dmgBanned = [];
        $dmgNormal = [];
        $dmgRaw    = [];

        $count     = 0;
        $avg       = 0;
        $max       = 0;
        $maxDamage = 0;

        foreach ($data as $entry) {
            $damage = intval($entry['weapon_damage']);

            if ($entry['ban_hash']) {
                $dmgBanned[$damage] = $entry['count'];
            } else {
                $dmgNormal[$damage] = $entry['count'];

                if ($entry['count'] >= 4) {
                    $max = $damage;
                }
            }

            if ($damage > $maxDamage) {
                $maxDamage = $damage;
            }
        }

        foreach ($rawData as $entry) {
            $damage = intval($entry['weapon_damage']);

            $dmgRaw[$damage] = $entry['count'];

            if ($count < $entry['count']) {
                $count = $entry['count'];
                $avg   = $damage;
            }
        }

        $max = $this->closest(array_keys($dmgRaw), $avg * 1.8);

        $damages = [
            'data'   => [
                [], [],
            ],
            'labels' => [],
            'names'  => ['weapons.damage_normal', 'weapons.damage_banned'],
            'avg'    => $avg,
            'max'    => $max[1],
        ];

        $raw = [
            'data'   => [
                [], [], [],
            ],
            'labels' => [],
            'names'  => ['weapons.damage_raw'],
            'avg'    => $avg,
            'max'    => $max[1],
        ];

        for ($x = 0; $x <= $maxDamage; $x++) {
            $normal = $dmgNormal[$x] ?? 0;
            $banned = $dmgBanned[$x] ?? 0;

            if ($normal === 0 && $banned === 0) {
                continue;
            }

            $damages['labels'][] = $x === 999 ? '999+ hp' : $x . 'hp';

            $damages['data'][0][] = $normal;
            $damages['data'][1][] = $banned;
        }

        for ($x = 0; $x <= $maxDamage; $x++) {
            $rawDmg = $dmgRaw[$x] ?? 0;

            $damages['labels'][] = $x === 999 ? '999+ hp' : $x . 'hp';

            $damages['data'][2][] = $rawDmg;
        }

        return $this->json(true, [
            'damages' => $damages,
            'raw'     => $raw,
            'hashes'  => [$hash, $unsigned],
        ]);
    }

    private function closest(array $array, int $number): array
    {
        $last = 0;

        foreach ($array as $index => $item) {
            if ($item > $number) {
                return [$index - 1, $last];
            }

            $last = $item;
        }

        return [sizeof($array) - 1, $last];
    }
}
