<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

class DiscordHelper
{
    public static function getUserInfo(int $id): ?array
    {
        $data = self::doRequest('GET', 'users/' . $id);

        $data['timestamp'] = ($id >> 22) + 1420070400000;

        return $data;
    }

    public static function findUserByName(string $name): ?array
    {
        $name = trim(strtolower($name));

        if (Str::startsWith($name, '@')) {
            $name = substr($name, 1);
        }

        $guilds = self::doRequest('GET', 'users/@me/guilds');

        foreach ($guilds as $guild) {
            $id = $guild['id'];

            $found = self::doRequest('GET', 'guilds/' . $id . '/members/search?query=' . urlencode($name) . '&limit=1');

            if (!empty($found)) {
                return $found[0];
            }
        }

        return null;
    }

    private static function doRequest(string $method, string $path): ?array
    {
        $token = env("DISCORD_BOT_TOKEN");

        if (!$token) {
            throw new \Exception("No discord bot token set.");
        }

        try {
            $client = new Client();

            $res = $client->request($method, 'https://discord.com/api/v10/' . $path, [
                'headers' => [
                    'Authorization' => 'Bot ' . $token,
                ]
            ]);

            $response = $res->getBody()->getContents();

            $data = json_decode($response, true);

            if (!$data) {
                throw new \Exception("Invalid response from `$path` api route.");
            } else if (!empty($data['message'])) {
                throw new \Exception($data['message']);
            }

            return $data;
        } catch (\Throwable $e) {
        }

        throw new \Exception("Failed to execute `$path` api route.");
    }
}
