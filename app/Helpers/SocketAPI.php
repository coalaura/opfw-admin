<?php

namespace App\Helpers;

use App\Server;
use GuzzleHttp\Client;

class SocketAPI
{
    /**
     * /data/players
     */
    public static function getPlayers(string $ip): array
    {
        return self::fresh($ip, 'GET', '/data/players') ?? [];
    }

    /**
     * Actually executes the route on the socket server.
     *
     * @param string $ip
     * @param string $method
     * @param string $route
     *
     * @return null|array
     */
    private static function fresh(string $ip, string $method, string $route): ?array
    {
        if (!HttpHelper::isLocalPortOpen(9999)) {
            return null;
        }

        $server = Server::getServerName($ip);

        if (!$server) {
            LoggingHelper::log(sprintf('No server name found for %s.', $ip));

            return null;
        }

        $token = sessionKey();

        if (!$token) {
            return false;
        }

        $url = sprintf('http://localhost:9999/socket/%s/%s', $server, ltrim($route, '/'));

        $client = new Client(
            [
                'verify'          => false,
                'timeout'         => 1,
                'connect_timeout' => 1,
                'http_errors'     => false,
            ]
        );

        try {
            Timer::start(sprintf('SocketAPI::fresh %s %s', $method, $route));

            $response = $client->request($method, $url, [
                'query' => [
                    'token' => $token,
                ],
            ]);

            $body = $response->getBody()->getContents();

            Timer::stop();

            $status = $response->getStatusCode();

            if ($status % 2 !== 0) {
                return null;
            }

            $json = json_decode($body, true);

            if (!$json || empty($json['status']) || !$json['status']) {
                return null;
            }

            return $json['data'] ?? null;
        } catch (\Exception $exception) {
            LoggingHelper::log(sprintf('SocketAPI::fresh %s failed: %s', $url, $exception->getMessage()));
        }

        return null;
    }
}
