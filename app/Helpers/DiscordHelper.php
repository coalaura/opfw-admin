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

        $guilds = self::getMyGuilds();

        if (empty($guilds)) {
            throw new \Exception("Bot is not in any guilds, unable to perform search.");
        }

        foreach ($guilds as $guild) {
            $id = $guild['id'];

            $found = self::doRequest('GET', 'guilds/' . $id . '/members/search?query=' . urlencode($name) . '&limit=1');

            if (!empty($found)) {
                return $found[0];
            }
        }

        $guildNames = array_map(function ($guild) {
            return $guild['name'];
        }, $guilds);

        throw new \Exception("No user found. Searched guilds: " . implode(', ', $guildNames) . ".");
    }

    private static function getMyGuilds(): ?array
    {
        $guild = env("DISCORD_GUILD_ID");

        if ($guild && is_numeric($guild)) {
            return [
                [
                    'id'   => $guild,
                    'name' => '#' . $guild,
                ],
            ];
        }

        return self::doRequest('GET', 'users/@me/guilds');
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
                ],
            ]);

            $response = $res->getBody()->getContents();

            $data = json_decode($response, true);

            LoggingHelper::log("Discord API response `$path`: " . trim($response));

            if (!$data && $data !== []) {
                throw new \Exception("Invalid response from `$path` api route.");
            } else if (!empty($data['message'])) {
                throw new \Exception($data['message']);
            }

            return $data;
        } catch (\Throwable $e) {
            LoggingHelper::log("Failed to execute `$path` api route: " . $e->getMessage());
        }

        throw new \Exception("Failed to execute `$path` api route.");
    }
}
