<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\WeaponDamageEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $jobs  = ServerAPI::getDefaultJobs();
        $items = ServerAPI::getItems();

        return Inertia::render('Tools/Config', [
            'jobs'  => $jobs['jobs'] ?? [],
            'items' => $items ?? [],
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
     * @param Request $request
     * @return Response
     */
    public function weapons(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $weapons = WeaponDamageEvent::getWeaponListFlat();

        // Collect usages
        $usages = CacheHelper::read('weapon_usages');

        if (!$usages) {
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

                if (!$type || $type === "throwable" || $type === "misc" || $type === "melee") continue;

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
     * @param Request $request
     * @param int $hash
     */
    public function searchWeapons(Request $request, int $hash)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $weapons = WeaponDamageEvent::getWeaponListFlat();

        if (!isset($weapons[$hash])) {
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
            ->whereNotIn('hit_component', [19, 20]) // Neck and head shots
            ->groupBy(['weapon_damage', 'ban_hash'])
            ->get()->toArray();

        if (empty($data)) {
            return $this->json(false, null, 'No data');
        }

        $dmgBanned = [];
        $dmgNormal = [];

        $count     = 0;
        $avg       = 0;
        $maxDamage = 0;

        foreach ($data as $entry) {
            $damage = intval($entry['weapon_damage']);

            if ($entry['ban_hash']) {
                $dmgBanned[$damage] = $entry['count'];
            } else {
                $dmgNormal[$damage] = $entry['count'];

                if ($count < $entry['count']) {
                    $count = $entry['count'];
                    $avg   = $damage;
                }
            }

            if ($damage > $maxDamage) {
                $maxDamage = $damage;
            }
        }

        $max = $this->closest(array_keys($dmgNormal), $avg * 5);

        $damages = [
            'data'   => [
                [], [],
            ],
            'labels' => [],
            'names'  => ['weapons.damage_normal', 'weapons.damage_banned'],
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

        return $this->json(true, [
            'damages' => $damages,
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
