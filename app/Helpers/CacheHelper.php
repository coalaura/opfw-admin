<?php

namespace App\Helpers;

use App\Log;
use App\Player;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

class CacheHelper
{
    const MINUTE = 60;
    const HOUR = self::MINUTE * 60;
    const DAY = self::HOUR * 24;
    const WEEK = self::DAY * 7;
    const MONTH = self::DAY * 30;
	const YEAR = self::DAY * 365;

    /**
     * Loads a map for licenseIdentifier->PlayerName
     *
     * @param array $identifiers
     * @return array
     */
    public static function loadLicensePlayerNameMap(array $identifiers): array
    {
        $cache = new CacheFile('license_map', function() use ($identifiers) {
            $players = Player::query()->whereIn('license_identifier', $identifiers)->select([
                'license_identifier', 'player_name',
            ])->get();

            $data = [];

            foreach ($players as $player) {
                $data[$player->license_identifier] = $player->getSafePlayerName();
            }

            return $data;
        }, 30 * self::MINUTE, function($data) use ($identifiers) {
            foreach ($identifiers as $identifier) {
                if (!isset($data[$identifier])) {
                    return true;
                }
            }

            return false;
        });

        $data = $cache->get();

        $filtered = [];

        foreach ($identifiers as $identifier) {
            if (!isset($data[$identifier])) {
                continue;
            }

            $filtered[$identifier] = $data[$identifier];
        }

        return $filtered;
    }

    /**
     * Array of possible log actions
     *
     * @param bool $forceRefresh
     * @return array
     */
    public static function getLogActions(bool $forceRefresh = false): array
    {
        $actions = self::read('actions', null);

        if (!$actions || $forceRefresh) {
            $actions = Log::query()->selectRaw('action, COUNT(action) as count')->groupBy('action')->get()->toArray();

            self::write('actions', $actions, self::HOUR * 2);
        }

        return $actions;
    }

    /**
     * Clear the entire cache
     */
    public static function clear()
    {
        Cache::store('file')->clear();
    }

    /**
     * Forget something in the cache
     *
     * @param string $key
     */
    public static function forget(string $key)
    {
        if (CLUSTER && !Str::startsWith($key, CLUSTER)) {
            $key = CLUSTER . $key;
        }

        if (!Cache::store('file')->has($key)) {
            return;
        }

        try {
            Cache::store('file')->forget($key);
        } catch (InvalidArgumentException $e) {
        }
    }

    /**
     * Write something to the cache
     *
     * @param string $key
     * @param $data
     * @param int|null $ttl
     */
    public static function write(string $key, $data, ?int $ttl = null)
    {
        if (CLUSTER && !Str::startsWith($key, CLUSTER)) {
            $key = CLUSTER . $key;
        }

        try {
            Cache::store('file')->set($key, $data, $ttl);
        } catch (InvalidArgumentException $e) {
        }
    }

    /**
     * Read something from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function read(string $key, $default = null)
    {
        if (CLUSTER && !Str::startsWith($key, CLUSTER)) {
            $key = CLUSTER . $key;
        }

        try {
            if (!Cache::store('file')->has($key)) {
                return $default;
            }

            return Cache::store('file')->get($key, $default);
        } catch (InvalidArgumentException $e) {
        }

        return $default;
    }

    /**
     * Check if something exists in cache
     *
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool
    {
        if (CLUSTER && !Str::startsWith($key, CLUSTER)) {
            $key = CLUSTER . $key;
        }

        try {
            return Cache::store('file')->has($key);
        } catch (InvalidArgumentException $e) {
        }

        return false;
    }
}
