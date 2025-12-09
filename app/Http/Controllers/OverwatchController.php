<?php
namespace App\Http\Controllers;

use App\Character;
use App\Helpers\HttpHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\Mutex;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\RootHelper;
use App\Helpers\ServerAPI;
use App\Helpers\SocketAPI;
use App\Helpers\StatusHelper;
use App\Player;
use App\Server;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverwatchController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
            abort(401);
        }

        return Inertia::render('Overwatch/Index');
    }

    /**
     * Live streams of new players.
     *
     * @param Request $request
     * @return Response
     */
    public function live(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
            abort(401);
        }

        return Inertia::render('Overwatch/Live', [
            'replay' => HttpHelper::isPortInUse(4644),
        ]);
    }

    /**
     * Save a replay of a stream.
     *
     * @param string $license
     */
    public function replay(string $license)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
            abort(401);
        }

        if (! HttpHelper::isPortInUse(4644)) {
            return self::json(false, null, 'Replay server is not running.');
        }

        $spectator = $this->resolveSpectatorOrReject($license);

        if (! $spectator) {
            return;
        }

        $client = new Client([
            'timeout'         => 10,
            'connect_timeout' => 2,
            'http_errors'     => true,
        ]);

        try {
            $res = $client->get(sprintf('http://localhost:4644/%s', $spectator['key']));

            return $res->getBody();
        } catch (\Exception $e) {
            LoggingHelper::log(sprintf('Could not get replay: %s', $e->getMessage()));

            return self::json(false, null, "Could not get replay.");
        }
    }

    /**
     * Perform an action on a spectator.
     *
     * @param string $license
     * @param string $action
     */
    public function doAction(string $license, string $action)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
            abort(401);
        }

        $spectator = $this->resolveSpectatorOrReject($license);

        if (! $spectator) {
            return;
        }

        switch ($action) {
            case 'revive':
                OPFWHelper::revivePlayer($license);

                break;
            case 'new_player':
                $players = Player::getNewPlayers()->filter(function ($player) use ($spectator) {
                    $status = StatusHelper::get($player->license_identifier);

                    if (! $status || ! $status['character']) {
                        return false;
                    }

                    if ($spectator['spectating'] && $spectator['spectating']['license'] === $player->license_identifier) {
                        return false;
                    }

                    return true;
                })->values();

                if (empty($players)) {
                    return self::json(false, null, 'No new players found.');
                }

                $player = $players[rand(0, sizeof($players) - 1)];
                $status = StatusHelper::get($player->license_identifier);

                session_put('isRandom', true);

                return redirect()->action(
                    [OverwatchController::class, 'setSpectating'],
                    [
                        'license' => $license,
                        'source'  => $status['source'],
                    ]
                );
            case 'center':
                ServerAPI::setGameplayCamera($spectator['server'], $license, 2500, 0, 0);

                break;
            case 'backwards':
                ServerAPI::setGameplayCamera($spectator['server'], $license, 2500, 0, 180);

                break;
            case 'left':
                ServerAPI::setGameplayCamera($spectator['server'], $license, 2500, 0, 90);

                break;
            case 'right':
                ServerAPI::setGameplayCamera($spectator['server'], $license, 2500, 0, -90);

                break;
            default:
                return self::json(false, null, 'Invalid action.');
        }

        return self::json(true);
    }

    /**
     * Set a player to be spectated.
     *
     * @param string $license
     * @param int $source
     */
    public function setSpectating(string $license, int $source)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
            abort(401);
        }

        $isReset  = $source === 0;
        $isRandom = session_get('isRandom');

        if ($isRandom) {
            session_forget('isRandom');
        }

        if ($source < 0 || $source > 65535) {
            return self::json(false, null, 'Invalid server id.');
        }

        $spectator = $this->resolveSpectatorOrReject($license);

        if (! $spectator) {
            return;
        }

        if ($isReset && ! $spectator['spectating']) {
            return self::json(true);
        } else if (! $isReset && $spectator['spectating'] && $spectator['spectating']['source'] === $source) {
            return self::json(true);
        }

        if (! $isReset) {
            $target = StatusHelper::source($source);

            if (! $target || ! $target['character']) {
                return self::json(false, null, 'Target is not connected to the server or does not have a character loaded.');
            }
        }

        $spectatorUser = StatusHelper::get($license);

        if (! $spectatorUser) {
            return self::json(false, null, 'Spectator is not connected to the server.');
        }

        $mutex = new Mutex(sprintf('live_spectate_%s', $license));

        if (! $mutex->lock()) {
            return self::json(false, null, 'Already processing a spectate request for this spectator. Please wait a moment and try again.');
        }

        // Check if character is loaded, if not load first character that we find
        if (! $spectatorUser['character']) {
            if ($isReset) {
                return self::json(true);
            }

            $character = Character::query()
                ->where('license_identifier', '=', $license)
                ->where('character_deleted', '=', 0)
                ->first();

            if (! $character) {
                return self::json(false, null, 'Spectator has no characters that can be loaded.');
            }

            $response = ServerAPI::loadCharacter($spectator['server'], $license, $character->character_id);

            if (! $response) {
                return self::json(false, null, 'Failed to load character.');
            }

            sleep(5);
        }

        // Ensure spectator mode is enabled
        $player = Player::query()
            ->where('license_identifier', '=', $license)
            ->first();

        if (! $player) {
            return self::json(false, null, 'Could not find spectator player.');
        }

        try {
            $this->ensureSpectatorSettings($player, $spectator['server'], $spectatorUser['source']);
        } catch (\Exception $e) {
            return self::json(false, null, $e->getMessage());
        }

        // Actually do the spectating
        if ($isReset) {
            $command = "spectate";
            $message = sprintf('%s reset #%d', user()->player_name, $spectator['id']);
        } else {
            $command = sprintf("spectate %d", $source);
            $message = sprintf('%s set #%d to %d%s', user()->player_name, $spectator['id'], $source, $isRandom ? ' - rng' : '');
        }

        $response = ServerAPI::runCommand($spectator['server'], $license, $command);

        if (! $response) {
            return self::json(false, null, 'Failed to make spectator spectate target.');
        }

        SocketAPI::putPanelChatMessage($spectator['ip'], $message);

        return self::json(true);
    }

    private function ensureSpectatorSettings(Player $player, string $server, int $source)
    {
        $updated = false;
        $license = $player->license_identifier;

        // Ensure player has sufficient permissions
        $hasPermissions = $player->hasEnabledPermissions("advanced_metagame", "idle");

        if (! $player->isSeniorStaff() || ! $player->isBot() || ! $hasPermissions) {
            $enabled = $player->enabled_commands ?? [];

            $enabled[] = "advanced_metagame";
            $enabled[] = "idle";

            $enabled = array_values(array_unique($enabled));

            $player->update([
                "is_bot"           => 1,
                "is_staff"         => 1,

                "enabled_commands" => $enabled,
            ]);

            ServerAPI::refreshUser($server, $license);
        }

        // Ensure spectator mode is enabled
        if (! $player->isSpectatorModeEnabled()) {
            $updated = true;

            ServerAPI::setSpectatorMode($server, $license, true);
        }

        // Ensure spectator camera is enabled
        if (! $player->isSpectatorCameraEnabled()) {
            $updated = true;

            ServerAPI::setSpectatorCamera($server, $license, true);
        }

        // Ensure idle cam is disabled
        if (! $player->isIdleCamDisabled()) {
            $updated = true;

            ServerAPI::runCommand($server, $license, "disable_idle");
        }

        // Ensure advanced metagame is enabled
        if (! $player->isAdvancedMetagameEnabled()) {
            $updated = true;

            ServerAPI::runCommand($server, $license, "advanced_metagame 1");
        }

        // Ensure we are away from other players
        if ($updated) {
            ServerAPI::teleportPlayer($server, $source, -1908.02, -573.42, 19.09);
        }
    }

    /**
     * Get a screenshot and some data belonging to it from a random player.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getRandomScreenshot(Request $request): \Illuminate\Http\Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
            return self::json(false, null, 'You can not use the screenshot functionality');
        }

        $players = StatusHelper::all();

        $players = array_filter($players, function ($player) {
            return $player && $player['character'] && ! RootHelper::isUserRoot($player['license']) && ! in_array('in_shell', $player["characterData"]) && ! $player['fakeDisconnected'] && ! $player['inQueue'];
        });

        if (! empty($players)) {
            $license = array_rand($players);
            $player  = $players[$license];

            if (! $player['character']) {
                return self::json(false, null, "Failed to get character info of the player.");
            }

            $screenshot = ServerAPI::createScreenshot($player['server'], $player['source']);

            if (! $screenshot) {
                return self::json(false, null, "Failed to obtain a screenshot of the player.");
            }

            return self::json(true, [
                "license"   => $license,
                "url"       => $screenshot['screenshotURL'],
                "id"        => $player['source'],
                "server"    => Server::getServerName($player['server']),
                "character" => [
                    "name" => $player['character']['name'],
                    "id"   => $player['character']['id'],
                ],
            ]);
        } else {
            return self::json(false, null, "There are no players available.");
        }
    }

    /**
     * Get a spectator by license.
     *
     * @param string $license
     * @return array|null
     */
    private function resolveSpectatorOrReject(string $license): ?array
    {
        $server = Server::getFirstServer();

        if (! $server) {
            LoggingHelper::log("No opfw server found while trying to resolve spectator.");

            self::json(false, null, "No opfw server found.")->send();

            return null;
        }

        $spectators = SocketAPI::getSpectators($server['ip']);
        $spectator  = false;

        foreach ($spectators as $id => $spec) {
            if ($spec['license'] === $license) {
                $spectator = $spec;

                $spectator['id']     = $id + 1;
                $spectator['ip']     = $server['ip'];
                $spectator['server'] = $server['name'];

                break;
            }
        }

        if (! $spectator) {
            LoggingHelper::log("Failed to find spectator with license {$license}.");

            self::json(false, null, "Failed to find spectator.")->send();

            return null;
        }

        return $spectator;
    }
}
