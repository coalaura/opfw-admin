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
        if (!$refresh) {
            if (CacheHelper::exists("opfw_vehicles_txt")) {
                return CacheHelper::read("opfw_vehicles_txt", []);
            }
        }

        $data = self::fresh('GET', '/vehicles.txt', null, self::MediumCacheTime);

        if (!$data) {
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
                $parts = explode(" - ", $vehicle);

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
        if (!self::$forceRefresh) {
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

        if (!$serverUrl) {
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

        if (!$token) {
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
                throw new \Exception(sprintf('HTTP %s: %s', $status, substr($body, 0, 100)));
            }

            if (Str::endsWith($url, '.json') || $forceJson) {
                // Sometimes the server sends stupid json responses with invalid characters
                $body = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $body);

                $json = json_decode($body, true);

                if (!$json || !isset($json['statusCode'])) {
                    throw new \Exception(sprintf('Invalid JSON response %s: %s', $status, substr($body, 0, 100)));
                }

                $status = intval($json['statusCode']) ?? $status;

                if ($status < 200 || $status > 299) {
                    throw new \Exception(sprintf('Invalid JSON status %s', $status));
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
