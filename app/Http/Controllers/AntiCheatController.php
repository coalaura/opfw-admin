<?php

namespace App\Http\Controllers;

use App\Ban;
use App\Helpers\PermissionHelper;
use App\Player;
use App\Screenshot;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AntiCheatController extends Controller
{
    const IgnoreAntiCheatTypes = [
        'modified_fov',
        'using_macro'
    ];

    /**
     * All Anti-Cheat screenshots.
     *
     * @param Request $request
     * @return Response
     */
    public function render(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ANTI_CHEAT)) {
            abort(401);
        }

        $page = Paginator::resolveCurrentPage('page');

        $whereNot = implode(' AND ', array_map(function ($type) {
            return "type != '$type'";
        }, self::IgnoreAntiCheatTypes));

        $query = "SELECT id, player_name, users.license_identifier, users.player_aliases, url, details, metadata, timestamp, users.playtime FROM (" .
            "SELECT CONCAT('s_', id) as id, license_identifier, screenshot_url as url, type as details, metadata, timestamp FROM anti_cheat_events WHERE screenshot_url IS NOT NULL AND $whereNot" .
            " UNION " .
            "SELECT CONCAT('b_', id) as id, identifier, ban_hash, reason, null as metadata, MAX(timestamp) FROM user_bans WHERE SUBSTRING_INDEX(identifier, ':', 1) = 'license' AND SUBSTRING_INDEX(reason, '-', 1) IN ('MODDING', 'INJECTION', 'NO_PERMISSIONS', 'ILLEGAL_VALUES', 'TIMEOUT_BYPASS') AND smurf_account IS NULL GROUP BY identifier" .
            ") data LEFT JOIN users ON data.license_identifier = users.license_identifier WHERE users.license_identifier IS NOT NULL ORDER BY timestamp DESC LIMIT 20 OFFSET " . (($page - 1) * 20);

        $system = DB::select(DB::raw($query));

        $identifiers = array_values(array_map(function ($entry) {
            return $entry->license_identifier;
        }, $system));

        $system = array_map(function ($entry) {
            if (Str::startsWith($entry->id, 'b_')) {
                $entry->reason = Ban::resolveAutomatedReason($entry->details)['reason'];
            } else {
                $entry->type    = $entry->details;
                $entry->details = ucwords(strtolower(str_replace('_', ' ', $entry->details)));
            }

            $entry->player_name = Player::getFilteredPlayerName($entry->player_name ?? "", $entry->player_aliases, $entry->license_identifier ?? "");

            $entry->metadata = json_decode($entry->metadata, true);

            return $entry;
        }, $system);

        $reasons = Ban::getAutomatedReasons();

        return Inertia::render('AntiCheat/Index', [
            'screenshots' => $system,
            'links'       => $this->getPageUrls($page),
            'banMap'      => Ban::getAllBans(false, $identifiers, true),
            'page'        => $page,
            'reasons'     => [
                'MODDING'   => $reasons['MODDING'],
                'INJECTION' => $reasons['INJECTION'],
            ],
        ]);
    }
}
