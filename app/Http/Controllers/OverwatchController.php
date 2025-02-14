<?php
namespace App\Http\Controllers;

use App\Character;
use App\Helpers\GeneralHelper;
use App\Helpers\Mutex;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\Helpers\SocketAPI;
use App\Helpers\StatusHelper;
use App\Player;
use App\Server;
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

        return Inertia::render('Overwatch/Live');
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

        $serverName = Server::getFirstServer('name');
        $serverIp = Server::getFirstServer('ip');

        if (! $serverIp) {
            return self::json(false, null, 'No OP-FW server found.');
        }

        $spectators = SocketAPI::getSpectators($serverIp);
        $valid      = false;

        foreach ($spectators as $spectator) {
            if ($spectator['license'] === $license) {
                $valid = true;

                break;
            }
        }

        if (! $valid) {
            return self::json(false, null, 'Invalid spectator.');
        }

        $target = StatusHelper::source($source);

        if (!$target || !$target['character']) {
            return self::json(false, null, 'Target is not connected to the server or does not have a character loaded.');
        }

        $spectator = StatusHelper::get($license);

        if (!$spectator) {
            return self::json(false, null, 'Spectator is not connected to the server.');
        }

        $mutex = new Mutex(sprintf('live_spectate_%s', $license));

        if (!$mutex->lock()) {
            return self::json(false, null, 'Already processing a spectate request for this spectator. Please wait a moment and try again.');
        }

        if (!$spectator['character']) {
            $character = Character::query()
                ->where('license_identifier', '=', $license)
                ->where('character_deleted', '=', 0)
                ->first();

            if (! $character) {
                return self::json(false, null, 'Player has no character loaded and no loadable character available.');
            }

            $response = ServerAPI::loadCharacter($serverName, $license, $character->character_id);

            if (!$response) {
                return self::json(false, null, 'Failed to load character.');
            }

            sleep(5);
        }

        $response = ServerAPI::runCommand($serverName, $license, sprintf("spectate %d", $source));

        if (! $response) {
            return self::json(false, null, 'Failed to make spectator spectate target.');
        }

        return self::json(true, null, 'Player is now being spectated.');
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
}
