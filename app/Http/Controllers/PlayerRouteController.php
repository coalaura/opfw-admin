<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Player;
use App\Screenshot;
use App\Server;
use App\WeaponDamageEvent;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayerRouteController extends Controller
{
    const AllowedIdentifiers = [
        'steam',
        'discord',
        'fivem',
        'license',
        'license2',
        'live',
        'xbl',
    ];

    const EnablableCommands = [
        "cam_clear",
        "cam_play",
        "cam_point",
        "disable_idle_cam",
        "freecam",
        "orbitcam",
        "player_stats",
    ];

    /**
     * Kick a player from the game
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function kick(Player $player, Request $request): RedirectResponse
    {
        if (empty(trim($request->input('reason')))) {
            return backWith('error', 'Reason cannot be empty');
        }

        $user = user();

        $staffName = $user->player_name;

        if (env('HIDE_BAN_CREATOR')) {
            $staffName = "a staff member";
        }

        $reason = $request->input('reason') ?: 'You have been kicked by ' . $staffName;

        return OPFWHelper::kickPlayer($user->license_identifier, $user->player_name, $player, $reason)->redirect();
    }

    /**
     * Send a staffPM to a player
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function staffPM(Player $player, Request $request): RedirectResponse
    {
        $user    = user();
        $message = trim($request->input('message'));

        if (empty($message)) {
            return backWith('error', 'Message cannot be empty');
        }

        return OPFWHelper::staffPM($user->license_identifier, $player, $message)->redirect();
    }

    /**
     * Unload someones character
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function unloadCharacter(Player $player, Request $request): RedirectResponse
    {
        $user      = user();
        $character = trim($request->input('character'));

        if (empty($character)) {
            return backWith('error', 'Character ID cannot be empty');
        }

        $message = trim($request->input('message'));

        return OPFWHelper::unloadCharacter($user->license_identifier, $player, $character, $message)->redirect();
    }

    /**
     * Returns all linked accounts
     *
     * @param Player $player
     * @param Request $request
     * @return Response
     */
    public function linkedAccounts(Player $player, Request $request): Response
    {
        $identifiers = $player->getBannableIdentifiers();
        $linked      = [
            'total'  => 0,
            'linked' => [],
        ];

        $players = Player::query()->whereRaw("JSON_OVERLAPS(identifiers, '" . json_encode($identifiers) . "') = 1")->groupBy('license_identifier')->get();

        $last = $player->getLastUsedIdentifiers();

        foreach ($identifiers as $identifier) {
            if (!isset($linked['linked'][$identifier])) {
                $linked['linked'][$identifier] = [
                    'label'     => Player::getIdentifierLabel($identifier) ?? 'Unknown Identifier',
                    'accounts'  => [],
                    'last_used' => in_array($identifier, $last),
                ];
            }

            $accounts = [];

            foreach ($players as $p) {
                if ($p->license_identifier !== $player->license_identifier && in_array($identifier, $p->getIdentifiers())) {
                    $accounts[] = [
                        'license_identifier' => $p->license_identifier,
                        'player_name'        => $p->player_name,
                    ];
                }
            }

            $linked['linked'][$identifier]['accounts'] = $accounts;

            $linked['total'] += sizeof($accounts);
        }

        return (new Response([
            'status' => true,
            'data'   => $linked,
        ], 200))->header('Content-Type', 'application/json');
    }

    /**
     * Returns all linked accounts
     *
     * @param Player $player
     * @return Response
     */
    public function linkedHWID(Player $player): Response
    {
        return (new Response([
            'status' => true,
            'data'   => $player->getHWIDBanHash(),
        ], 200))->header('Content-Type', 'application/json');
    }

    /**
     * Returns all discord accounts
     *
     * @param Player $player
     * @param Request $request
     * @return Response
     */
    public function discordAccounts(Player $player, Request $request): Response
    {
        return (new Response([
            'status' => true,
            'data'   => $player->getDiscordInfo(),
        ], 200))->header('Content-Type', 'application/json');
    }

    /**
     * Returns all anti cheat information.
     *
     * @param Player $player
     * @param Request $request
     * @return Response
     */
    public function antiCheat(Player $player, Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ANTI_CHEAT)) {
            abort(401);
        }

        $events = DB::table("anti_cheat_events")->where('license_identifier', $player->license_identifier)->orderByDesc("timestamp")->limit(200)->get()->toArray();

        return (new Response([
            'status' => true,
            'data'   => array_map(function ($entry) {
                $entry->metadata = json_decode($entry->metadata);

                return $entry;
            }, $events),
        ], 200))->header('Content-Type', 'application/json');
    }

    /**
     * Returns ban information.
     *
     * @param Player $player
     * @return Response
     */
    public function ban(Player $player): Response
    {
        $ban = $player->getActiveBan();

        return (new Response([
            'status' => true,
            'data'   => $ban ? [
                'hash'      => $ban->ban_hash,
                'timestamp' => $ban->getTimestamp(),
            ] : false,
        ], 200))->header('Content-Type', 'application/json');
    }

    /**
     * Revives the player
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function revivePlayer(Player $player, Request $request): RedirectResponse
    {
        $user = user();

        return OPFWHelper::revivePlayer($user->license_identifier, $player->license_identifier)->redirect();
    }

    /**
     * Removes a certain identifier
     *
     * @param Player $player
     * @param string $identifier
     * @param Request $request
     * @return RedirectResponse
     */
    public function removeIdentifier(Player $player, string $identifier, Request $request): RedirectResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can remove identifiers.');
        }

        $identifiers = $player->getIdentifiers();

        if (!in_array($identifier, $identifiers)) {
            return backWith('error', 'That identifier doesn\'t belong to the player.');
        }

        $type = explode(':', $identifier)[0];
        if (!in_array($type, self::AllowedIdentifiers)) {
            return backWith('error', 'You cannot remove the identifier of type "' . $type . '".');
        }

        $filtered = array_values(array_filter($identifiers, function ($id) use ($identifier) {
            return $id !== $identifier;
        }));

        $player->update([
            'identifiers' => $filtered,
        ]);

        return backWith('success', 'Identifier has been removed successfully.');
    }

    /**
     * Sets the soft ban status
     *
     * @param Player $player
     * @param int $status
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSoftBanStatus(Player $player, int $status, Request $request): RedirectResponse
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_SOFT_BAN)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $status = $status ? 1 : 0;

        $player->update([
            'is_soft_banned' => $status,
        ]);

        return backWith('success', 'Soft ban status has been updated successfully.');
    }

    /**
     * Sets the tag
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateTag(Player $player, Request $request): RedirectResponse
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_EDIT_TAG)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $tag = $request->input('tag') ? trim($request->input('tag')) : null;

        $player->update([
            'panel_tag' => $tag,
        ]);

        Player::resolveTags(true);

        return backWith('success', 'Tag has been updated successfully.');
    }

    /**
     * Updates the role
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateRole(Player $player, Request $request): RedirectResponse
    {
        if (!env('ALLOW_ROLE_EDITING', false) || !$this->isSuperAdmin($request)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $role = $request->input('role') ? trim($request->input('role')) : null;

        $data = [
            'is_trusted'      => 0,
            'is_staff'        => 0,
            'is_senior_staff' => 0,
        ];

        if ($role === 'seniorStaff') {
            $data['is_senior_staff'] = 1;
        } else if ($role === 'staff') {
            $data['is_staff'] = 1;
        } else if ($role === 'trusted') {
            $data['is_trusted'] = 1;
        }

        $player->update($data);

        return backWith('success', 'Role has been updated successfully.');
    }

    /**
     * Updates enabled commands
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateEnabledCommands(Player $player, Request $request): RedirectResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $enabledCommands = $request->input('enabledCommands');

        foreach ($enabledCommands as $command) {
            if (!in_array($command, self::EnablableCommands)) {
                return backWith('error', 'You cannot enable the command "' . $command . '".');
            }
        }

        $player->update([
            "enabled_commands" => $enabledCommands,
        ]);

        return backWith('success', 'Commands have been updated successfully.');
    }

    /**
     * Takes a screenshot
     *
     * @param string $server
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function screenshot(string $server, int $id, Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_SCREENSHOT)) {
            return self::json(false, null, 'You can not use the screenshot functionality');
        }

        $api = Server::getServerApiURLFromName($server);
        if (!$api) {
            return self::json(false, null, 'Invalid server');
        }

        $license = Server::isServerIDValid($id);
        if (!$license) {
            return self::json(false, null, 'Invalid server id (User is offline?)');
        }

        $status = Player::getOnlineStatus($license, true);

        $lifespan = $request->query('short') ? 3 * 60 : 60 * 60;

        $data = OPFWHelper::createScreenshot($api, $id, true, $lifespan);

        if ($data->status) {
            DB::table('panel_screenshot_logs')->insert([
                'source_license'   => license(),
                'target_license'   => $license,
                'target_character' => $status->character,
                'type'             => $request->query('short') ? 'screenshot_short' : 'screenshot',
                'url'              => $data->data['screenshotURL'],
                'timestamp'        => time(),
            ]);

            DB::table('panel_screenshot_logs')
                ->where('timestamp', '<', time() - CacheHelper::YEAR)
                ->delete();

            return self::json(true, [
                'url'     => $data->data['screenshotURL'],
                'logs'    => $data->data['logs'] ?? false,
                'license' => $license,
            ]);
        } else {
            return self::json(false, null, 'Failed to create screenshot');
        }
    }

    /**
     * Takes a screen capture
     *
     * @param string $server
     * @param int $id
     * @param int $duration
     * @param Request $request
     * @return Response
     */
    public function capture(string $server, int $id, int $duration, Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_SCREENSHOT)) {
            return self::json(false, null, 'Only trusted Panel users can use screenshot functionality');
        }

        $api = Server::getServerApiURLFromName($server);
        if (!$api) {
            return self::json(false, null, 'Invalid server');
        }

        if ($duration < 1 || $duration > 30) {
            return self::json(false, null, 'Invalid duration (1-30)');
        }

        $license = Server::isServerIDValid($id);
        if (!$license) {
            return self::json(false, null, 'Invalid server id (User is offline?)');
        }

        $status = Player::getOnlineStatus($license, true);

        $data = OPFWHelper::createScreenCapture($api, $id, $duration);

        if ($data->status) {
            DB::table('panel_screenshot_logs')->insert([
                'source_license'   => license(),
                'target_license'   => $license,
                'target_character' => $status->character,
                'type'             => 'screencapture',
                'url'              => $data->data['screenshotURL'],
                'timestamp'        => time(),
            ]);

            DB::table('panel_screenshot_logs')
                ->where('timestamp', '<', time() - CacheHelper::YEAR)
                ->delete();

            return self::json(true, [
                'url'     => $data->data['screenshotURL'],
                'logs'    => $data->data['logs'] ?? false,
                'license' => $license,
            ]);
        } else {
            return self::json(false, null, 'Failed to create screen capture');
        }
    }

    /**
     * @param Player $player
     * @param Request $request
     * @return Response
     */
    public function attachScreenshot(Player $player, Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_SCREENSHOT)) {
            return self::json(false, null, 'You can not use the screenshot functionality');
        }

        $screenshotUrl = trim($request->input('url')) ?? '';

        $re = '/^https:\/\/api\.op-framework\.com\/files\/public\/\d{1,2}-\d{1,2}-\d{4}-\w+\.jpg$/m';
        if (!preg_match($re, $screenshotUrl)) {
            return self::json(false, null, 'Invalid screenshot url');
        }

        $note = trim($request->input('note')) ?? '';
        if (strlen($note) > 500) {
            return self::json(false, null, 'Note cannot be longer than 500 characters');
        }

        $fileName = md5($screenshotUrl) . '.jpg';

        $exists = !!Screenshot::query()->where('filename', '=', $fileName)->first();
        if ($exists) {
            return self::json(false, null, 'Screenshot already exists');
        }

        $dir = storage_path('screenshots');

        if (!file_exists($dir)) {
            mkdir($dir);
        }

        $screenshot = null;
        try {
            $client = new Client(
                [
                    'verify' => false,
                ]
            );

            $res = $client->request('GET', $screenshotUrl);

            $screenshot = $res->getBody()->getContents();
        } catch (\Throwable $t) {
            LoggingHelper::log("Failed to download screenshot from " . $screenshotUrl);
            LoggingHelper::log(get_class($t) . ': ' . $t->getMessage());
        }

        if (!$screenshot) {
            return self::json(false, null, 'Failed to download screenshot');
        }

        if (!put_contents($dir . '/' . $fileName, $screenshot)) {
            return self::json(false, null, 'Failed to store screenshot');
        }

        Screenshot::query()->create([
            'license_identifier' => $player->license_identifier,
            'filename'           => $fileName,
            'note'               => $note ?? '',
            'created_at'         => time(),
        ]);

        return self::json(true, 'Screenshot was attached to players profile successfully');
    }

    /**
     * @param string $screenshot
     * @return BinaryFileResponse
     */
    public function exportScreenshot(string $screenshot): BinaryFileResponse
    {
        if (!preg_match('/^\w{32}\.jpg$/m', $screenshot)) {
            abort(400);
        }

        $path = storage_path('screenshots') . '/' . $screenshot;

        return response()->file($path, [
            'Content-type: image/jpeg',
        ]);
    }

    /**
     * @param string $license
     */
    public function whoDamaged(Request $request, string $license)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_DAMAGE_LOGS)) {
            abort(401);
        }

        if (!$license || !Str::startsWith($license, 'license:')) {
            abort(404);
        }

        $player = Player::query()->select(['player_name', 'license_identifier'])->where('license_identifier', '=', $license)->get()->first();

        if (!$player) {
            abort(404);
        }

        $includeNPCs = $request->input('npcs') ?? false;

        $logs = WeaponDamageEvent::getDamaged($player->license_identifier, $includeNPCs);

        return $this->renderDamageLogs("🡐 🡐 🡐", "Who damaged", $player, $logs, false, false);
    }

    /**
     * @param string $license
     */
    public function whoWasDamagedBy(Request $request, string $license)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_DAMAGE_LOGS)) {
            abort(401);
        }

        if (!$license || !Str::startsWith($license, 'license:')) {
            abort(404);
        }

        $player = Player::query()->select(['player_name', 'license_identifier'])->where('license_identifier', '=', $license)->get()->first();

        if (!$player) {
            abort(404);
        }

        $includeNPCs = $request->input('npcs') ?? false;

        $logs = WeaponDamageEvent::getDamageDealtTo($player->license_identifier, $includeNPCs);

        return $this->renderDamageLogs("🡒 🡒 🡒", "Who was damaged by", $player, $logs, $includeNPCs, true);
    }

    private function renderDamageLogs($direction, $title, $player, $logs, $includeNPCs, $showNPCToggle)
    {
        $list = [];

        if (!empty($logs)) {
            $names = Player::fetchLicensePlayerNameMap($logs, 'license_identifier');

            $logs = array_map(function ($log) {
                $log["weapon_type"]        = WeaponDamageEvent::getDamageWeapon($log["weapon_type"]);
                $log["damage_type"]        = WeaponDamageEvent::getDamageType($log["damage_type"]);
                $log["hit_component"]      = WeaponDamageEvent::getHitComponent($log["hit_component"]);
                $log["action_result_name"] = WeaponDamageEvent::getActionName($log["action_result_name"]);

                $log["distance"] = number_format($log["distance"], 2) . "m";

                if (!isset($log["timestamp"])) {
                    $log["timestamp"] = round($log["timestamp_ms"] / 1000);
                }

                if (!isset($log["timestamp_ms"])) {
                    $log["timestamp_ms"] = $log["timestamp"] * 1000;
                }

                return $log;
            }, $logs);

            $maxName   = max(array_map('mb_strlen', array_values($names)));
            $maxWeapon = max(array_map(function ($log) {
                return strlen($log["weapon_type"]);
            }, $logs));
            $maxComponent = max(array_map(function ($log) {
                return strlen($log["hit_component"]);
            }, $logs));
            $maxDistance = max(array_map(function ($log) {
                return strlen($log["distance"]);
            }, $logs));
            $maxType = max(array_map(function ($log) {
                return strlen($log["damage_type"]);
            }, $logs));

            $lastDate = false;

            foreach ($logs as $index => $log) {
                $date = date('D, jS M Y', $log["timestamp"]);
                $time = '<i style="color:#ffb3b3">' . date('H:i:s', $log["timestamp"]) . '</i>';

                if ($lastDate !== $date) {
                    $list[] = "\n<b style='border-bottom:1px dashed #fff;margin-top:10px;display:inline-block'>- - - " . $date . " - - -</b>";
                    $list[] = "<i style='color:rgba(215,215,215,.7);line-height:1;margin-bottom:5px;display:inline-block'>" . $direction . "</i>";

                    $lastDate = $date;
                }

                $name = mb_str_pad($names[$log["license_identifier"]] ?? 'NPC', $maxName);
                $name = '<a href="/players/' . $log["license_identifier"] . '" style="color:#ffe3b3" target="_blank">' . $name . '</a>';

                $weapon    = '<span style="color:#bdffb3" title="weapon_type">' . str_pad($log["weapon_type"], $maxWeapon) . '</span>';
                $damage    = '<span style="color:#b3ffd9" title="weapon_damage">' . str_pad($log["weapon_damage"] . "hp", 5) . '</span>';
                $component = '<span style="color:#b3f6ff" title="hit_component">' . str_pad($log["hit_component"], $maxComponent) . '</span>';
                $distance  = '<span style="color:#b3c6ff" title="distance">' . str_pad($log["distance"], $maxDistance) . '</span>';
                $type      = '<span style="color:#cfb3ff" title="damage_type">' . str_pad($log["damage_type"], $maxType) . '</span>';
                $action    = '<span style="color:#ffb3ff" title="action_result_name">' . $log["action_result_name"] . '</span>';

                $list[] = "  " . $time . "    " . $name . "    " . $weapon . "    " . $damage . "    " . $component . "    " . $distance . "    " . $type . "    " . $action;

                $next = $logs[$index + 1] ?? false;

                if ($next) {
                    $diff = $log["timestamp_ms"] - $next["timestamp_ms"];

                    if ($diff <= 10000) {
                        if ($diff < 1000) {
                            $diff .= "ms";
                        } else {
                            $sec = floor($diff / 1000);
                            $ms  = $diff % 1000;

                            $diff = $sec . "s " . ($ms > 0 ? $ms . "ms" : "");
                        }

                        $list[] = "    <i style='color:rgba(255,179,179,.7);display:inline-block;margin:2px 0;line-height:1'>🡑 " . $diff . "</i>";
                    }
                }
            }
        } else {
            $list[] = 'No damage logs found';
        }

        $playerName = '<a href="/players/' . $player->license_identifier . '" target="_blank">' . $player->player_name . '</a>';

        $extra = "";

        if ($showNPCToggle) {
            $extra = "\n\n<small><i>" . ($includeNPCs ? "Including NPCs (<a href='?'>disable</a>)" : "Not including NPCs (<a href='?npcs=1'>enable</a>)") . "</i></small>";
        }

        return $this->fakeText(200, $title . " $playerName\n<small><i>All times in " . date("e") . "</i></small>" . $extra . "\n" . implode("\n", $list));
    }
}
