<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\HttpHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Player;
use App\Screenshot;
use App\Server;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        if (!$this->isSeniorStaff($request) && $status->isInShell()) {
            return self::json(false, null, 'Player is inside a house');
        }

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
                'flags'   => $status->characterMetadata,
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

        if (!$this->isSeniorStaff($request) && $status->isInShell()) {
            return self::json(false, null, 'Player is inside a house');
        }

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

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-type: image/jpeg',
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
