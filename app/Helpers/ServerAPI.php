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

    const LongRunningRoutes = [];

    /**
     * /items.json
     */
    public static function getItems(bool $refresh = false): array
    {
        return self::cached('GET', '/items.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /vehicles.json
     */
    public static function getVehicles(bool $refresh = false): array
    {
        return self::cached('GET', '/vehicles.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /weapons.json
     */
    public static function getWeapons(bool $refresh = false): array
    {
        return self::cached('GET', '/weapons.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /jobs.json
     */
    public static function getJobs(bool $refresh = false): array
    {
        return self::cached('GET', '/jobs.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /defaultJobs.json
     */
    public static function getDefaultJobs(bool $refresh = false): array
    {
        return self::cached('GET', '/defaultJobs.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /chatEmotes.json
     */
    public static function getChatEmotes(bool $refresh = false): array
    {
        return self::cached('GET', '/chatEmotes.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /routes.json
     */
    public static function getRoutes(bool $refresh = false): array
    {
        return self::cached('GET', '/routes.json', $refresh, self::MediumCacheTime) ?? [];
    }

    /**
     * /crafting.txt
     */
    public static function getCrafting(bool $refresh = false): string
    {
        return self::cached('GET', '/crafting.txt', $refresh, self::MediumCacheTime) ?? "";
    }

    /**
     * /variables.json
     */
    public static function getVariables(): array
    {
        return self::fresh('GET', '/variables.json') ?? [];
    }

    /**
     * Returns the cached value if it exists, otherwise refreshes the cache and returns the fresh value (if $refresh is true).
     *
     * @param string $method
     * @param string $route
     * @param bool $refresh
     * @param int $ttl
     *
     * @return null|array|bool|string
     */
    private static function cached(string $method, string $route, bool $refresh = false, int $ttl = self::ShortCacheTime)
    {
        $key = sprintf('opfw_%s', ltrim($route, '/'));

        if (CacheHelper::exists($key)) {
            return CacheHelper::read($key, null);
        }

        if ($refresh) {
            return self::fresh($method, $route, null, $ttl);
        }

        return null;
    }

    /**
     * Actually executes the route on the OP-FW server.
     *
     * @param string $method
     * @param string $route
     * @param array|null $data
     *
     * @return null|array|bool|string
     */
    private static function fresh(string $method, string $route, ?array $data = null, int $ttl = 0)
    {
        $token = env('OP_FW_TOKEN');

        if (!$token) {
            return null;
        }

        $server = Server::getFirstServer();

        $url = $server . ltrim($route, '/');

        $timeout = 2;

        if (self::isLongRunningRoute($route)) {
            $timeout = 30;
        } else if ($method !== 'GET') {
            $timeout = 6;
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
            Timer::start(sprintf('OPFWHelper::do %s %s', $method, $route));

            $response = $client->request($method, $url, [
                'query' => $data,
            ]);

            $body = $response->getBody()->getContents();

            Timer::stop();

            $status = $response->getStatusCode();
            $result = null;

            if ($status % 2 !== 0) {
                return null;
            }

            if (Str::endsWith($route, '.json')) {
                // Sometimes the server sends stupid json responses with invalid characters
                $body = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $body);

                $json = json_decode($body, true);

                if (!$json || !isset($json['statusCode'])) {
                    return null;
                }

                $status = intval($json['statusCode']) ?? $status;

                if ($status % 2 !== 0) {
                    return null;
                }

                $result = $json['data'] ?? null;
            } else if (Str::endsWith($route, '.txt')) {
                $result = $body;
            }

            if ($ttl > 0) {
                $key = sprintf('opfw_%s', ltrim($route, '/'));

                CacheHelper::write($key, $result, $ttl);
            }

            return $result;
        } catch (\Exception $exception) {
            LoggingHelper::log(sprintf('OPFWHelper::do %s %s failed: %s', $method, $url, $exception->getMessage()));
        }

        return null;
    }

    private static function isLongRunningRoute(string $route): bool
    {
        foreach (self::LongRunningRoutes as $longRunningRoute) {
            if (Str::startsWith($route, $longRunningRoute)) {
                return true;
            }
        }

        return false;
    }
}
