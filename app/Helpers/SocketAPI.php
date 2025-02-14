<?php
namespace App\Helpers;

use App\Server;
use GuzzleHttp\Client;

class SocketAPI
{
    public static function isUp(): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== "WIN") {
            return file_exists("/tmp/op-fw.lock");
        }

        $output = shell_exec("netstat -aon | findstr :9999");

        return strpos($output, "LISTENING") !== false;
    }
    /**
     * /data/players
     */
    public static function getPlayers(string $ip): array
    {
        return self::fresh($ip, 'GET', '/data/players') ?? [];
    }

    /**
     * /data/spectators
     */
    public static function getSpectators(string $ip): array
    {
        return self::fresh($ip, 'GET', '/data/spectators') ?? [];
    }

    /**
     * PUT /chat
     */
    public static function putPanelChatMessage(string $ip, string $message): ?array
    {
        return self::fresh($ip, 'PUT', '/chat', [
            'message' => $message,
        ]);
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
    private static function fresh(string $ip, string $method, string $route, ?array $data = null): ?array
    {
        if (! self::isUp()) {
            LoggingHelper::log('Socket server is not running (op-fw.sock not found).');

            return null;
        }

        $server = Server::getServerName($ip);

        if (! $server) {
            LoggingHelper::log(sprintf('No server name found for %s.', $ip));

            return null;
        }

        $token = sessionKey();

        if (! $token) {
            LoggingHelper::log('No session key found.');

            return null;
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
                'json'  => $data,
            ]);

            $body = $response->getBody()->getContents();

            Timer::stop();

            $status = $response->getStatusCode();

            if ($status % 2 !== 0) {
                throw new \Exception(sprintf('HTTP %s: %s', $status, substr($body, 0, 100)));
            }

            $json = json_decode($body, true);

            if (! $json || empty($json['status']) || ! $json['status']) {
                throw new \Exception(sprintf('Invalid JSON response %s: %s', $status, substr($body, 0, 100)));
            }

            return $json['data'] ?? null;
        } catch (\Exception $exception) {
            LoggingHelper::log(sprintf('SocketAPI::fresh %s failed: %s', $url, $exception->getMessage()));
        }

        return null;
    }
}
