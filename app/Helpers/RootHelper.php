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

        return array_values(array_filter(array_map(function($license) {
            $license = trim($license);

            if (empty($license) || !preg_match('/^license:/m', $license)) {
                return false;
            }

            return $license;
        }, $list)));
    }

    public static function isUserRoot(?string $license): bool
    {
        if (!$license) {
            return false;
        }

        $users = self::getRootUsers();

        return in_array($license, $users);
    }

    public static function isCurrentUserRoot(): bool
    {
        $user = user();

        if (!$user) {
            return false;
        }

        return self::isUserRoot($user->license_identifier);
    }
}
