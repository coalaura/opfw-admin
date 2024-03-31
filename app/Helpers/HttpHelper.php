<?php

namespace App\Helpers;

class HttpHelper
{
    private static string $error = "";

    /**
     * Starts a TCP connection to the given URL and returns true if successful
     *
     * @param string $url The URL to ping
     * @param int $timeout The timeout for the connection in milliseconds
     */
    public static function ping(string $url, int $timeout = 500): bool
    {
        $errno = 0;
        $errstr = "";

        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 80;

        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout / 1000.0);

        if ($connection) {
            fclose($connection);
        } else {
            self::$error = $errstr;
        }

        return !!$connection;
    }

    /**
     * Returns the last error message
     */
    public static function lastError(): string
    {
        return self::$error;
    }
}
