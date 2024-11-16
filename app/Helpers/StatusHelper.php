<?php

namespace App\Helpers;

use App\Server;

class StatusHelper
{
    private static CacheFile $cache;

    private static function init()
    {
        if (!isset(self::$cache)) {
            self::$cache = new CacheFile('status', function () {
                return self::fetch();
            }, 5);
        }
    }

    public static function all()
    {
        self::init();

        return self::$cache->get();
    }

    public static function get(string $license): ?array
    {
        self::init();

        return self::$cache->get()[$license] ?? null;
    }

    private static function parseCharacterFlags(int $flags): array
    {
        $data = [];

        !!($flags & 1) && $data[]   = 'dead';
        !!($flags & 2) && $data[]   = 'trunk';
        !!($flags & 4) && $data[]   = 'in_shell';
        !!($flags & 8) && $data[]   = 'invisible';
        !!($flags & 16) && $data[]  = 'invincible';
        !!($flags & 32) && $data[]  = 'frozen';
        !!($flags & 64) && $data[]  = 'spawned';
        !!($flags & 128) && $data[] = 'no_collisions';
        !!($flags & 256) && $data[] = 'no_gameplay_cam';

        return $data;
    }

    private static function fetch()
    {
        $servers = Server::getOPFWServers();

        if (!$servers) {
            return [];
        }

        $result = [];

        foreach ($servers as $server) {
            $users = SocketAPI::getPlayers($server['ip']);

            foreach ($users as $user) {
                $license = $user['license'];

                $user['server'] = $server['name'];
                $user['characterData'] = self::parseCharacterFlags($user['character'] ? $user['character']['flags'] : 0);

                $user['fakeDisconnected'] = !!(($user['flags'] ?? 0) & 2);
                $user['inQueue'] = !!(($user['flags'] ?? 0) & 16);

                $result[$license] = $user;
            }
        }

        return $result;
    }
}
