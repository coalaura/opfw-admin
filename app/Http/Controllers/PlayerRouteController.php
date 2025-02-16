<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\HttpHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\PanelLog;
use App\Player;
use App\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PlayerRouteController extends Controller
{
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

        $response = OPFWHelper::kickPlayer($user->player_name, $player, $reason);

        if (!$response->status) {
            return backWith('error', 'Failed to kick player');
        }

        PanelLog::log(
            $user->license_identifier,
            "Kicked Player",
            sprintf("%s kicked %s.", $user->consoleName(), $player->consoleName()),
            ['reason' => $reason]
        );

        if (!$player->isStaff()) {
            user()->trackStatistics('kicked-player');
        }

        return $response->redirect();
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

        $response = OPFWHelper::staffPM($user->license_identifier, $player, $message);

        if (!$response->status) {
            return backWith('error', 'Failed to send staffPM');
        }

        PanelLog::log(
            $user->license_identifier,
            "Staff PM",
            sprintf("%s sent a staffPM to %s.", $user->consoleName(), $player->consoleName()),
            ['message' => $message]
        );

        if (!$player->isStaff()) {
            user()->trackStatistics('sent-staff-pm');
        }

        return $response->redirect();
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

        $response = OPFWHelper::unloadCharacter($user->license_identifier, $player, $character, $message);

        if (!$response->status) {
            return backWith('error', 'Failed to unload character');
        }

        PanelLog::log(
            $user->license_identifier,
            "Unloaded Character",
            sprintf("%s unloaded %s.", $user->consoleName(), $player->consoleName()),
            ['message' => $message]
        );

        if (!$player->isStaff()) {
            user()->trackStatistics('unloaded-player');
        }

        return $response->redirect();
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
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_ANTI_CHEAT)) {
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
        $ban = $player->bans()->get()->first();

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

        $response = OPFWHelper::revivePlayer($player->license_identifier);

        if (!$response->status) {
            return backWith('error', 'Failed to revive player');
        }

        PanelLog::log(
            $user->license_identifier,
            "Revived Player",
            sprintf("%s revived %s.", $user->consoleName(), $player->consoleName())
        );

        if (!$player->isStaff()) {
            user()->trackStatistics('revived-player');
        }

        return backWith('success', 'Player has been revived');
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
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
            return self::json(false, null, 'You can not use the screenshot functionality');
        }

        if (!Server::getServerURL($server)) {
            return self::json(false, null, 'Invalid server');
        }

        $license = Server::isServerIDValid($id);
        if (!$license) {
            return self::json(false, null, 'Invalid server id (User is offline?)');
        }

        $status = Player::getOnlineStatus($license, true);

        if (!$this->isSeniorStaff($request) && $status->isInShell()) {
            return self::json(false, null, 'Player is inside a house');
        }

        $lifespan = $request->query('short') ? 3 * 60 : 60 * 60;

        $screenshot = ServerAPI::createScreenshot($server, $id, true, $lifespan);

        if (!$screenshot) {
            return self::json(false, null, 'Failed to create screenshot');
        }

        DB::table('panel_screenshot_logs')->insert([
            'source_license'   => license(),
            'target_license'   => $license,
            'target_character' => $status->character,
            'type'             => $request->query('short') ? 'screenshot_short' : 'screenshot',
            'url'              => $screenshot['screenshotURL'],
            'timestamp'        => time(),
        ]);

        DB::table('panel_screenshot_logs')
            ->where('timestamp', '<', time() - CacheHelper::YEAR)
            ->delete();

        return self::json(true, [
            'url'     => $screenshot['screenshotURL'],
            'logs'    => $screenshot['logs'] ?? false,
            'license' => $license,
            'flags'   => $status->characterMetadata,
        ]);
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
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
            return self::json(false, null, 'Only trusted Panel users can use screenshot functionality');
        }

        $api = Server::getServerURL($server);
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

        if (!$this->isSeniorStaff($request) && $status->isInShell()) {
            return self::json(false, null, 'Player is inside a house');
        }

        $screencapture = ServerAPI::createScreenCapture($api, $id, $duration, 30);

        if (!$screencapture) {
            return self::json(false, null, 'Failed to create screen capture');
        }

        DB::table('panel_screenshot_logs')->insert([
            'source_license'   => license(),
            'target_license'   => $license,
            'target_character' => $status->character,
            'type'             => 'screencapture',
            'url'              => $screencapture['screenshotURL'],
            'timestamp'        => time(),
        ]);

        DB::table('panel_screenshot_logs')
            ->where('timestamp', '<', time() - CacheHelper::YEAR)
            ->delete();

        return self::json(true, [
            'url'     => $screencapture['screenshotURL'],
            'logs'    => $screencapture['logs'] ?? false,
            'license' => $license,
        ]);
    }

    /**
     * Returns information about the last used IP.
     *
     * @param Player $player
     */
    public function playerIPInfo(Player $player)
    {
        $ip = $player->last_ip_identifier;

        if (!$ip) {
            return $this->jsonRaw([
                "success" => false,
                "error"   => 'No IP found',
            ]);
        }

        $info = HttpHelper::getIPInfo($ip);

        if (!$info || !$info["success"]) {
            return $this->jsonRaw([
                "success" => false,
                "error"   => 'Failed to get IP information',
            ]);
        }

        return $this->jsonRaw([
            "success" => true,
            "is_vpn"  => $info["is_vpn"] ?? false,
        ]);
    }
}
