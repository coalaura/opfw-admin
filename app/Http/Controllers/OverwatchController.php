<?php
namespace App\Http\Controllers;

use App\Character;
use App\Helpers\GeneralHelper;
use App\Helpers\HttpHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\Mutex;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\Helpers\SocketAPI;
use App\Helpers\StatusHelper;
use App\Player;
use App\Server;
use App\Warning;
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
            'emotes' => Warning::getAllReactions(),
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

        $spectator = $this->resolveSpectatorOrAbort($license);

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

        $this->resolveSpectatorOrAbort($license);

        switch ($action) {
            case 'revive':
                OPFWHelper::revivePlayer($license);
                break;
            case 'center':
                OPFWHelper::setGameplayCamera($license, 0, 0);
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

        if ($source < 0 || $source > 65535) {
            return self::json(false, null, 'Invalid server id.');
        }

        $isReset = $source === 0;

        $spectator = $this->resolveSpectatorOrAbort($license);

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

        $spectator = StatusHelper::get($license);

        if (! $spectator) {
            return self::json(false, null, 'Spectator is not connected to the server.');
        }

        $mutex = new Mutex(sprintf('live_spectate_%s', $license));

        if (! $mutex->lock()) {
            return self::json(false, null, 'Already processing a spectate request for this spectator. Please wait a moment and try again.');
        }

        if (! $spectator['character']) {
            if ($isReset) {
                return self::json(true);
            }

            $character = Character::query()
                ->where('license_identifier', '=', $license)
                ->where('character_deleted', '=', 0)
                ->first();

            if (! $character) {
                return self::json(false, null, 'Player has no character loaded and no loadable character available.');
            }

            $response = ServerAPI::loadCharacter($spectator['server'], $license, $character->character_id);

            if (! $response) {
                return self::json(false, null, 'Failed to load character.');
            }

            sleep(5);
        }

        if ($isReset) {
            $command = "spectate";
            $message = sprintf('%s reset stream #%d.', user()->player_name, $spectator['id']);
        } else {
            $command = sprintf("spectate %d", $source);
            $message = sprintf('%s set stream #%d to spectate %d.', user()->player_name, $spectator['id'], $source);
        }

        $response = ServerAPI::runCommand($spectator['server'], $license, $command);

        if (! $response) {
            return self::json(false, null, 'Failed to make spectator spectate target.');
        }

        SocketAPI::putPanelChatMessage($spectator['ip'], $message);

        return self::json(true);
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
            return $player && $player['character'] && ! GeneralHelper::isUserRoot($player['license']) && ! in_array('in_shell', $player["characterData"]) && ! $player['fakeDisconnected'] && ! $player['inQueue'];
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
    private function resolveSpectatorOrAbort(string $license): ?array
    {
        $server = Server::getFirstServer();

        if (! $server) {
            LoggingHelper::log("No opfw server found while trying to resolve spectator.");

            abort(500);
        }

        $spectators = SocketAPI::getSpectators($server['ip']);

        foreach ($spectators as $id => $spectator) {
            if ($spectator['license'] === $license) {
                $spectator['id'] = $id + 1;

                $spectator['ip'] = $server['ip'];
                $spectator['server'] = $server['name'];

                return $spectator;
            }
        }

        abort(400);
    }
}
