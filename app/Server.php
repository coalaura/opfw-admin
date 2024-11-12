<?php

namespace App;

use App\Helpers\GeneralHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\StatusHelper;
use Illuminate\Support\Str;

class Server
{
	public string $url;

    /**
     * Gets the API data.
     *
     * @return array
     */
    public function fetchApi(): array
    {
        $data = GeneralHelper::get(self::fixApiUrl($this->url) . 'variables.json') ?? null;

        $response = OPFWHelper::parseResponse($data);

        return $response->status && $response->data ? $response->data : [];
    }

    /**
     * Gets the api url
     *
     * @param string $serverIp
     * @return string
     */
    public static function fixApiUrl(string $serverIp): string
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

        return $serverIp;
    }

    public static function getServerName(string $serverIp): string
    {
        $serverIp = self::fixApiUrl($serverIp);

        $host = str_replace('https://', '', $serverIp);
        $host = str_replace('http://', '', $host);
        $host = explode('/', $host)[0];

        $name = env('NAME_' . str_replace(['.', ':'], '_', $host), CLUSTER . "s1");

        return preg_match('/^\d+\.\d+\.\d+\.\d+(:\d+)?$/m', $host) ? $name : explode('.', $host)[0];
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

    /**
     * Resolves the server api url from its name
     *
     * @param string $name
     * @return string|null
     */
    public static function getServerApiURLFromName(string $name): ?string
    {
        $rawServerIps = explode(',', env('OP_FW_SERVERS', ''));

        foreach ($rawServerIps as $rawServerIp) {
            $n = Server::getServerName($rawServerIp);
            if ($n === $name) {
                return self::fixApiUrl($rawServerIp);
            }
        }

        return null;
    }

    /**
     * Returns all servers
     *
     * @return array
     */
    public static function getAllServers(): array
	{
		$rawServerIps = explode(',', env('OP_FW_SERVERS', ''));

		$servers = [];

		foreach ($rawServerIps as $rawServerIp) {
			$server = new Server();

			$server->url = $rawServerIp;

			$servers[] = $server;
		}

		return $servers;
	}

    /**
     * Returns all server names
     *
     * @return array
     */
    public static function getAllServerNames(): array
    {
        $rawServerIps = explode(',', env('OP_FW_SERVERS', ''));

        $serverNames = [];
        foreach ($rawServerIps as $rawServerIp) {
            $serverNames[] = Server::getServerName($rawServerIp);
        }

        return $serverNames;
    }

    /**
     * Returns the first server found
     *
     * @return string|null
     */
    public static function getFirstServer(): ?string
    {
        $rawServerIps = explode(',', env('OP_FW_SERVERS', ''));

        return empty($rawServerIps) ? null : self::fixApiUrl($rawServerIps[0]);
    }

    /**
     * Returns the first server ip found
     *
     * @return string|null
     */
    public static function getFirstServerIP(): ?string
    {
        $rawServerIps = explode(',', env('OP_FW_SERVERS', ''));

        return empty($rawServerIps) ? null : $rawServerIps[0];
    }

}
