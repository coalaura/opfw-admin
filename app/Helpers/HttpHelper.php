<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;

class HttpHelper
{
    private static string $error = "";

    /**
     * Starts a TCP connection to the given URL and returns true if successful
     *
     * @param string $url The URL to ping
     * @param int $timeout The timeout for the connection in milliseconds
     * @return bool
     */
    public static function ping(string $url, int $timeout = 500): bool
    {
        $errno  = 0;
        $errstr = "";

        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 80;

        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout / 1000.0);

        if ($connection) {
            fclose($connection);
        } else {
            self::$error = sprintf("%s (%s)", $errstr, $errno);
        }

        return !!$connection;
    }

    /**
     * Returns true if the given port is open on the current machine
     *
     * @param int $port The port to check
     * @return bool
     */
    public static function isLocalPortOpen(int $port): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
            $output = shell_exec("netstat -aon | findstr :$port");

            return strpos($output, "LISTENING") !== false;
        } else {
            $output = shell_exec("netstat -atlpn | grep :" . $port);

            return strpos($output, "LISTEN") !== false;
        }
    }

    /**
     * Returns the last error message
     */
    public static function lastError(): string
    {
        return self::$error;
    }

    public static function getIPInfo(string $ip): ?array
    {
        $key = "ip_info_" . $ip;

        if (CacheHelper::exists($key)) {
            return CacheHelper::read($key);
        }

        $client = new Client([
            'timeout'         => 10,
            'connect_timeout' => 2,
            'http_errors'     => false,
        ]);

        try {
            $res = $client->get("https://vpn.shrt.day/" . $ip);

            $json = json_decode((string) $res->getBody(), true);

            if ($json && $json['success']) {
                CacheHelper::write($key, $json, CacheHelper::MINUTE * 5);

                return $json;
            }
        } catch (\Throwable $t) {}

        return null;
    }

    public static function get(string $url): string
    {
        $client = new Client([
            'timeout'         => 10,
            'connect_timeout' => 10,
            'http_errors'     => false,
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
                'Accept-Language' => 'en-US,en;q=0.7,de;q=0.3',
            ],
        ]);

        try {
            $res = $client->get($url);

            return (string) $res->getBody();
        } catch (\Throwable $t) {}

        return null;
    }

    public static function getRedirect(string $url): string
    {
        $client = new Client([
            'timeout'         => 10,
            'connect_timeout' => 10,
            'http_errors'     => false,
            'allow_redirects' => true,
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
                'Accept-Language' => 'en-US,en;q=0.7,de;q=0.3',
            ],
            'on_stats'        => function (TransferStats $stats) use (&$url) {
                $url = (string) $stats->getEffectiveUri();
            },
        ]);

        try {
            $client->get($url);
        } catch (\Throwable $t) {}

        return $url;
    }
}
