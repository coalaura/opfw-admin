<?php

namespace App\Helpers;

class RootHelper
{
    private static function getRootUsers(): array
    {
        $list = explode(",", getenv("ROOT_USERS") ?? env("ROOT_USERS") ?? "");

        if (!$list || !is_array($list)) {
            return [];
        }

        return array_values(array_filter(array_map(function($discord) {
            $discord = trim($discord);

            if (empty($discord) || !preg_match('/^\d{18}$/m', $discord)) {
                return false;
            }

            return $discord;
        }, $list)));
    }

    public static function isUserRoot(?string $discord): bool
    {
        if (!$discord) {
            return false;
        }

        $users = self::getRootUsers();

        return in_array($discord, $users);
    }

    public static function isCurrentUserRoot(): bool
    {
        $user = user();

        if (!$user) {
            return false;
        }

        return self::isUserRoot($user->discordId());
    }
}
