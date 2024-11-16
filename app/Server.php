<?php

namespace App;

use App\Helpers\CacheHelper;
use App\Helpers\GeneralHelper;
use App\Helpers\HttpHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\StatusHelper;
use Illuminate\Support\Str;

class Server
{
    /**
     * Gets the API data.
     *
     * @return array
     */
    public static function fetchApi(): array
    {
        $url = self::getFirstServer('url');

        if (!$url) {
            return [];
        }

        $data = GeneralHelper::get($url . 'variables.json') ?? null;

        $response = OPFWHelper::parseResponse($data);

        return $response->status && $response->data ? $response->data : [];
    }

    /**
     * Gets the api url
     *
     * @param string $serverIp
     * @return string
     */
    private static function resolveUrlAndDomain(string $serverIp): array
    {
        $serverIp = Str::finish(trim($serverIp), '/');

        if (!Str::endsWith($serverIp, '/op-framework/')) {
            $serverIp .= 'op-framework/';
        }

        if (!Str::startsWith($serverIp, 'https://')) {
            $serverIp = 'https://' . $serverIp;
        }

        if (Str::contains($serverIp, 'localhost')) {
            $serverIp = preg_replace('/^https?:\/\//m', 'http://', $serverIp);
        }

        $port = parse_url($serverIp, PHP_URL_PORT);

        return [
            $serverIp,
            parse_url($serverIp, PHP_URL_HOST) . ($port ? ':' . $port : '')
        ];
    }

    public static function getOPFWServers(?string $key = null): array
    {
        $servers = explode(';', env('OP_FW_SERVERS', ''));

        $list = [];

        foreach ($servers as $server) {
            if (Str::contains($server, ',')) {
                $parts = explode(',', $server);

                $name = $parts[0];
                $server = $parts[1];
            }

            [$url, $domain] = self::resolveUrlAndDomain($server);

            $list[] = [
                'name' => $name ?? $domain,
                'url' => $url,
                'ip' => $domain
            ];
        }

        if ($key) {
            return array_map(function ($server) use ($key) {
                return $server[$key];
            }, $list);
        }

        return $list;
    }

    public static function getServerName(string $serverIp): ?string
    {
        $servers = self::getOPFWServers();

        foreach ($servers as $server) {
            if ($server['ip'] === $serverIp || $server['url'] === $serverIp) {
                return $server['name'];
            }
        }

        return null;
    }

    public static function getServerURL(string $serverName): ?string
    {
        $servers = self::getOPFWServers();

        foreach ($servers as $server) {
            if ($server['name'] === $serverName) {
                return $server['url'];
            }
        }

        return null;
    }

    /**
     * Returns the first server found
     *
     * @return string|array|null
     */
    public static function getFirstServer(?string $key = null)
    {
        $servers = self::getOPFWServers();
        $first = first($servers);

        if (!$first) {
            return null;
        }

        return $key ? $first[$key] : $first;
    }

    /**
     * Resolves the fivem:// url from the connect url
     */
    public static function getConnectUrl(bool $refresh = false): string
    {
        $url = Server::getFirstServer("ip");

        $cache = 'connect_' . md5($url);

        if ($refresh) {
            $redirect = HttpHelper::getRedirect($url);

            if (Str::startsWith($redirect, 'https://cfx.re/join/')) {
                $redirect = str_replace('https://', 'fivem://connect/', $redirect);
            }

            CacheHelper::write($cache, $redirect, 4 * CacheHelper::HOUR);
        }

        if (CacheHelper::exists($cache)) {
            return CacheHelper::read($cache, '');
        }

        return '';
    }

    /**
     * @param int $id
     * @return bool|string
     */
    public static function isServerIDValid(int $id)
    {
        $players = StatusHelper::all();

        foreach ($players as $license => $player) {
            if ($player['source'] === $id) {
                return $license;
            }
        }

        return false;
    }

}
