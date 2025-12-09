<?php
namespace App\Helpers;

use App\Server;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class ServerAPI
{
    const ShortCacheTime  = 6 * CacheHelper::HOUR;
    const MediumCacheTime = 12 * CacheHelper::HOUR;
    const LongCacheTime   = 2 * CacheHelper::DAY;

    private static bool $forceRefresh = false;

    /**
     * Forces all requests to be refreshed.
     */
    public static function forceRefresh(): void
    {
        self::$forceRefresh = true;
    }

    /**
     * /items.json
     */
    public static function getItems(bool $refresh = false): array
    {
        return self::cached('/items.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /vehicles.json
     */
    public static function getVehicles(bool $refresh = false): array
    {
        return self::cached('/vehicles.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /weapons.json
     */
    public static function getWeapons(bool $refresh = false): array
    {
        return self::cached('/weapons.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /peds.json
     */
    public static function getPeds(bool $refresh = false): array
    {
        return self::cached('/peds.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /jobs.json
     */
    public static function getJobs(bool $refresh = false): array
    {
        return self::cached('/jobs.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /defaultJobs.json
     */
    public static function getDefaultJobs(bool $refresh = false): array
    {
        return self::cached('/defaultJobs.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /chatEmotes.json
     */
    public static function getChatEmotes(bool $refresh = false): array
    {
        return self::cached('/chatEmotes.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /routes.json
     */
    public static function getRoutes(bool $refresh = false): array
    {
        return self::cached('/routes.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /permissions.json
     */
    public static function getPermissions(bool $refresh = false): array
    {
        return self::cached('/permissions.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /config.json
     */
    public static function getConfig(bool $refresh = false): array
    {
        return self::cached('/config.json', $refresh, self::ShortCacheTime) ?? [];
    }

    /**
     * /config.json
     */
    public static function getConfigFresh(): array
    {
        return self::fresh('GET', '/config.json') ?? [];
    }

    /**
     * /crafting.txt
     */
    public static function getCrafting(bool $refresh = false): string
    {
        return self::cached('/crafting.txt', $refresh, self::MediumCacheTime) ?? "";
    }

    /**
     * /variables.json
     */
    public static function getVariables(): array
    {
        return self::fresh('GET', '/variables.json') ?? [];
    }

    /**
     * /vehicles.txt
     */
    public static function getVehiclesTxt(bool $refresh = false): array
    {
        if (! $refresh) {
            if (CacheHelper::exists("opfw_vehicles_txt")) {
                return CacheHelper::read("opfw_vehicles_txt", []);
            }
        }

        $data = self::fresh('GET', '/vehicles.txt', null, self::MediumCacheTime);

        if (! $data) {
            return [];
        }

        $list = [];

        $re = '/^([^\s]+)\n((\t.+\n)+)/mi';
        preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0);

        foreach ($matches as $match) {
            $resource = trim($match[1]);
            $vehicles = explode("\n", $match[2]);

            $entries = [];

            foreach ($vehicles as $vehicle) {
                $vehicle = trim($vehicle);
                $parts   = explode(" - ", $vehicle);

                if (sizeof($parts) < 2) {
                    continue;
                }

                $entries[] = [
                    'label' => $parts[0],
                    'model' => $parts[1],
                ];
            }

            $list[$resource] = $entries;
        }

        CacheHelper::write("opfw_vehicles_txt", $list, self::MediumCacheTime);

        return $list;
    }

    /**
     * /execute/createScreenshot
     */
    public static function createScreenshot(string $server, int $source, bool $drawHTML = true, int $lifespan = 3600)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/createScreenshot';

        return self::do('POST', $url, [
            'serverId' => $source,
            'lifespan' => $lifespan,
            'drawHTML' => $drawHTML,
        ], 10, true);
    }

    /**
     * /execute/createScreenCapture
     */
    public static function createScreenCapture(string $server, int $source, int $duration, int $fps, int $lifespan = 3600)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/createScreenshot';

        return self::do('POST', $url, [
            'serverId' => $source,
            'lifespan' => $lifespan,
            'fps'      => $fps,
            'duration' => $duration * 1000,
        ], $duration + 15, true);
    }

    /**
     * /execute/loadCharacter
     */
    public static function loadCharacter(string $server, string $licenseIdentifier, int $characterId)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/loadCharacter';

        return self::do('POST', $url, [
            'licenseIdentifier' => $licenseIdentifier,
            'characterId'       => $characterId,
        ], 3, true);
    }

    /**
     * /execute/runCommand
     */
    public static function runCommand(string $server, string $targetLicense, string $command)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/runCommand';

        return self::do('POST', $url, [
            'targetLicense' => $targetLicense,
            'command'       => $command,
        ], 3, true);
    }

    /**
     * /execute/setGameplayCamera
     */
    public static function setGameplayCamera(string $server, string $targetLicense, int $duration, float $pitch, float $heading)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/setGameplayCamera';

        return self::do('PATCH', $url, [
            'targetLicense' => $targetLicense,
            'duration'      => $duration,
            'pitch'         => $pitch,
            'heading'       => $heading,
        ], 3, false);
    }

    /**
     * /execute/setSpectatorCamera
     */
    public static function setSpectatorCamera(string $server, string $targetLicense, bool $enabled)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/setSpectatorCamera';

        return self::do('PATCH', $url, [
            'targetLicense' => $targetLicense,
            'enabled'       => $enabled ? 'true' : '',
        ], 3, false);
    }

    /**
     * /execute/setSpectatorMode
     */
    public static function setSpectatorMode(string $server, string $targetLicense, bool $enabled)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/setSpectatorMode';

        return self::do('PATCH', $url, [
            'targetLicense' => $targetLicense,
            'enabled'       => $enabled ? 'true' : '',
        ], 3, false);
    }

    /**
     * /execute/teleportPlayer
     */
    public static function teleportPlayer(string $server, int $source, float $x, float $y, float $z)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/teleportPlayer';

        return self::do('POST', $url, [
            'targetSource' => $source,
            'x'            => $x,
            'y'            => $y,
            'z'            => $z,
        ], 3, true);
    }

    /**
     * /execute/kickPlayer
     */
    public static function kickPlayer(string $server, string $licenseIdentifier, string $message)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/kickPlayer';

        return self::do('POST', $url, [
            'licenseIdentifier'       => $licenseIdentifier,
            'reason'                  => $message,
            'removeReconnectPriority' => false,
        ], 3, true);
    }

    /**
     * /execute/refreshUser
     */
    public static function refreshUser(string $server, string $licenseIdentifier)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/refreshUser';

        return self::do('POST', $url, [
            'licenseIdentifier' => $licenseIdentifier,
        ], 3, true);
    }

    /**
     * /execute/showUserNotifications
     */
    public static function showUserNotifications(string $server, int $targetSource)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/showUserNotifications';

        return self::do('POST', $url, [
            'targetSource' => $targetSource,
        ], 3, true);
    }

    /**
     * /execute/validateAuthToken
     */
    public static function validateAuthToken(string $server, string $licenseIdentifier, string $token)
    {
        $url = Server::getServerURL($server);

        $url .= 'execute/validateAuthToken';

        return self::do('POST', $url, [
            'licenseIdentifier' => $licenseIdentifier,
            'token'             => $token,
        ], 3, true);
    }

    /**
     * Returns the cached value if it exists, otherwise refreshes the cache and returns the fresh value (if $refresh is true).
     *
     * @param string $route
     * @param bool $refresh
     * @param int $ttl
     *
     * @return null|array|bool|string
     */
    private static function cached(string $route, bool $refresh = false, int $ttl = self::ShortCacheTime)
    {
        if (! self::$forceRefresh) {
            $key = sprintf('opfw_%s', ltrim($route, '/'));

            if (CacheHelper::exists($key)) {
                return CacheHelper::read($key, null);
            }
        } else {
            $refresh = true;
        }

        if ($refresh) {
            return self::fresh('GET', $route, null, $ttl);
        }

        return null;
    }

    /**
     * Prepares the route and executes it on the OP-FW server.
     *
     * @param string $method
     * @param string $route
     * @param array|null $data
     *
     * @return null|array|bool|string
     */
    private static function fresh(string $method, string $route, ?array $data = null, int $ttl = 0)
    {
        $serverUrl = Server::getFirstServer('url');

        if (! $serverUrl) {
            LoggingHelper::log('No OP-FW server found.');

            return null;
        }

        $url = sprintf('%s/%s', rtrim($serverUrl, '/'), ltrim($route, '/'));

        $timeout = $method === 'GET' ? 2 : 6;

        $result = self::do($method, $url, $data, $timeout);

        if ($result !== null && $ttl > 0) {
            $key = sprintf('opfw_%s', ltrim($route, '/'));

            CacheHelper::write($key, $result, $ttl);
        }

        return $result;
    }

    /**
     * Actually executes the route on the OP-FW server.
     *
     * @param string $method
     * @param string $url
     * @param array|null $data
     * @param int $timeout
     *
     * @return null|array|bool|string
     */
    private static function do(string $method, string $url, ?array $data = null, int $timeout = 2, bool $forceJson = false)
    {
        $token = env('OP_FW_TOKEN');

        if (! $token) {
            LoggingHelper::log('No OP-FW token found.');

            return null;
        }

        $client = new Client(
            [
                'verify'          => false,
                'timeout'         => $timeout,
                'connect_timeout' => 1,
                'http_errors'     => false,
                'headers'         => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        try {
            Timer::start(sprintf('ServerAPI::do %s %s', $method, $url));

            $response = $client->request($method, $url, [
                'query' => $data,
            ]);

            $body = $response->getBody()->getContents();

            Timer::stop();

            $status = $response->getStatusCode();
            $result = null;

            if ($status < 200 || $status > 299) {
                throw new \Exception(sprintf('HTTP %s: %s', $status, $body));
            }

            if (Str::endsWith($url, '.json') || $forceJson) {
                // Sometimes the server sends stupid json responses with invalid characters
                $body = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $body);

                $json = json_decode($body, true);

                if (! $json || ! isset($json['statusCode'])) {
                    throw new \Exception(sprintf('Invalid JSON response %s: %s', $status, $body));
                }

                $status = intval($json['statusCode']) ?? $status;

                if ($status < 200 || $status > 299) {
                    throw new \Exception(sprintf('Invalid JSON status %s: %s', $status, $json['message'] ?? 'No message'));
                }

                if (! empty($json['message'])) {
                    return $json['message'];
                }

                $result = $json['data'] ?? null;
            } else if (Str::endsWith($url, '.txt')) {
                $result = $body;
            }

            return $result;
        } catch (\Exception $exception) {
            LoggingHelper::log(sprintf('ServerAPI::do %s %s failed: %s', $method, $url, $exception->getMessage()));
        }

        return null;
    }
}
