<?php
namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\Player;
use App\WeaponDamageEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ToolController extends Controller
{
    public function paintings()
    {
        return Inertia::render('Tools/Paintings');
    }

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
     * Damage distribution.
     *
     * @return Response
     */
    public function damages(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_DAMAGE_LOGS)) {
            abort(401);
        }

        $details = [
            "target" => null,
            "before" => null,
            "after"  => null,
        ];

        $query = WeaponDamageEvent::query()
            ->where('damage_type', '=', '3') // 3 = BULLET
            ->where('is_parent_self', '=', '1')
            ->whereNotNull('hit_player');

        if ($license = $request->input("license")) {
            $query->where("license_identifier", "=", $license);

            $player = Player::where('license_identifier', '=', $license)->first();

            $details["target"] = [
                "license" => $license,
                "name"    => $player ? $player->getSafePlayerName() : "Unknown",
            ];
        }

        if ($before = $request->input("before")) {
            $time = strtotime($before . " 00:00:00");

            if ($time) {
                $query->where("timestamp", "<", $time * 1000);

                $details["before"] = date("m/d/Y h:i A", $time);
            }
        }

        if ($after = $request->input("after")) {
            $time = strtotime($after . " 23:59:59");

            if ($time) {
                $query->where("timestamp", ">", $time * 1000);

                $details["after"] = date("m/d/Y h:i A", $time);
            }
        }

        $query->select(DB::raw("hit_component, SUM(1) as amount"))->groupBy("hit_component");

        $data = $query->get();

        $max     = 0;
        $damages = [];

        foreach (WeaponDamageEvent::HitComponents as $component => $_) {
            $damages[$component] = 0;
        }

        foreach ($data as $entry) {
            $component = intval($entry->hit_component);
            $amount    = intval($entry->amount);

            $damages[$component] = $amount;

            $max = max($max, $amount);
        }

        return Inertia::render('Tools/Damage', [
            'filters' => [
                'license' => $request->input('license'),
                'before'  => $request->input('before'),
                'after'   => $request->input('after'),
            ],
            'details' => $details,
            'damages' => $damages,
            'names'   => WeaponDamageEvent::HitComponents,
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
            ->select([DB::raw('COUNT(weapon_damage_events.id) as count'), 'weapon_damage'])
            ->leftJoin('user_bans', 'identifier', '=', 'license_identifier')
            ->where('weapon_damage_events.timestamp', '>', time() - 60 * 60 * 24 * 120 * 1000)
            ->where('is_parent_self', '=', '1')
            ->whereIn('weapon_type', [$hash, $unsigned])
            ->whereNotNull('hit_player')
            ->whereNull('ban_hash')
            ->where('hit_player', '!=', '')
            ->groupBy('weapon_damage')
            ->get()->toArray();

        $dmgBanned = [];
        $dmgNormal = [];
        $dmgRaw    = [];

        $actualRaw    = [];
        $actualNormal = [];

        $count     = 0;
        $avg       = 0;
        $max       = 0;
        $maxDamage = 0;
        $maxRaw    = 0;

        foreach ($data as $entry) {
            $damage = intval($entry['weapon_damage']);
            $capped = min($damage, 400);

            if ($entry['ban_hash']) {
                $dmgBanned[$capped] = ($dmgBanned[$capped] ?? 0) + $entry['count'];
            } else {
                $dmgNormal[$capped]    = ($dmgNormal[$capped] ?? 0) + $entry['count'];
                $actualNormal[$damage] = ($actualNormal[$damage] ?? 0) + $entry['count'];
            }

            if ($capped > $maxDamage) {
                $maxDamage = $capped;
            }
        }

        foreach ($rawData as $entry) {
            $damage = intval($entry['weapon_damage']);
            $capped = min($damage, 200);

            $dmgRaw[$capped]    = ($dmgRaw[$capped] ?? 0) + $entry['count'];
            $actualRaw[$damage] = ($actualRaw[$damage] ?? 0) + $entry['count'];

            if ($capped > $maxRaw) {
                $maxRaw = $capped;
            }
        }

        $keys = array_keys($actualRaw);

        sort($keys);

        foreach ($keys as $damage) {
            $amount  = $actualRaw[$damage];
            $players = $actualNormal[$damage] ?? 0;

            if ($amount >= 10 && $players >= 2) {
                $max = $damage;
            }

            if ($count < $amount) {
                $count = $amount;
                $avg   = $damage;
            }
        }

        $damages = [
            'data'   => [
                [], [],
            ],
            'labels' => [],
            'names'  => ['weapons.damage_normal', 'weapons.damage_banned'],
            'avg'    => $avg,
            'max'    => $max + 2,
        ];

        $raw = [
            "datasets" => [
                [
                    "label"           => "Raw",
                    "data"            => [],
                    "backgroundColor" => 'rgba(100, 235, 55, 0.3)',
                    "borderColor"     => 'rgba(100, 235, 55, 1)',
                    "pointRadius"     => 0,
                ],
            ],
            'labels'   => [],
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

        for ($x = 0; $x <= $maxRaw; $x++) {
            $rawDmg = $dmgRaw[$x] ?? 0;

            $raw['labels'][] = $x === 400 ? '400+ hp' : $x . 'hp';

            $raw['datasets'][0]['data'][] = $rawDmg;
        }

        return $this->json(true, [
            'damages' => $damages,
            'raw'     => $raw,
            'hashes'  => [$hash, $unsigned],
        ]);
    }
}
