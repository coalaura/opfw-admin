<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class PermissionHelper
{
    const PERMISSIONS = [
        self::PERM_SCREENSHOT     => ['screenshot', self::LEVEL_STAFF],
        self::PERM_SUSPICIOUS     => ['suspicious', self::LEVEL_SENIOR],
        self::PERM_ADVANCED       => ['advanced', self::LEVEL_SENIOR],
        self::PERM_LIVEMAP        => ['livemap', self::LEVEL_STAFF],
        self::PERM_LOCK_BAN       => ['lock_ban', self::LEVEL_SENIOR],
        self::PERM_SOFT_BAN       => ['soft_ban', self::LEVEL_ROOT],
        self::PERM_EDIT_TAG       => ['edit_tag', self::LEVEL_ROOT],
        self::PERM_LOADING_SCREEN => ['loading_screen', self::LEVEL_SUPERADMIN],
        self::PERM_VIEW_QUEUE     => ['view_queue', self::LEVEL_SENIOR],
        self::PERM_TWITTER        => ['twitter', self::LEVEL_SUPERADMIN],
        self::PERM_TWITTER_VERIFY => ['twitter_verify', self::LEVEL_SUPERADMIN],
        self::PERM_TWITTER_EDIT   => ['twitter_edit', self::LEVEL_SUPERADMIN],
        self::PERM_LINKED         => ['linked', self::LEVEL_ROOT],
        self::PERM_ANNOUNCEMENT   => ['announcement', self::LEVEL_SUPERADMIN],
        self::PERM_DAMAGE_LOGS    => ['damage_logs', self::LEVEL_SENIOR],
        self::PERM_CRAFTING       => ['crafting', self::LEVEL_SUPERADMIN],
        self::PERM_PHONE_LOGS     => ['phone_logs', self::LEVEL_SUPERADMIN],
        self::PERM_MONEY_LOGS     => ['money_logs', self::LEVEL_STAFF],
        self::PERM_ANTI_CHEAT     => ['anti_cheat', self::LEVEL_STAFF],
        self::PERM_DARK_CHAT      => ['dark_chat', self::LEVEL_SENIOR],
        self::PERM_BAN_EXCEPTION  => ['ban_exception', self::LEVEL_SUPERADMIN],
        self::PERM_WHITELIST      => ['whitelist', self::LEVEL_SUPERADMIN],
        self::PERM_API_TOKENS     => ['api_tokens', self::LEVEL_SUPERADMIN],
        self::PERM_REALTY_EDIT    => ['realty_edit', self::LEVEL_SUPERADMIN],
        self::PERM_SAVINGS_LOGS   => ['savings_logs', self::LEVEL_SENIOR],
        self::PERM_BULK_DELETE    => ['bulk_delete', self::LEVEL_SUPERADMIN],
    ];

    const PERM_SCREENSHOT     = 'P_SCREENSHOT';
    const PERM_SUSPICIOUS     = 'P_SUSPICIOUS';
    const PERM_ADVANCED       = 'P_ADVANCED';
    const PERM_LIVEMAP        = 'P_LIVEMAP';
    const PERM_LOCK_BAN       = 'P_LOCK_BAN';
    const PERM_SOFT_BAN       = 'P_SOFT_BAN';
    const PERM_EDIT_TAG       = 'P_EDIT_TAG';
    const PERM_LOADING_SCREEN = 'P_LOADING_SCREEN';
    const PERM_VIEW_QUEUE     = 'P_VIEW_QUEUE';
    const PERM_TWITTER        = 'P_TWITTER';
    const PERM_TWITTER_VERIFY = 'P_TWITTER_VERIFY';
    const PERM_TWITTER_EDIT   = 'P_TWITTER_EDIT';
    const PERM_LINKED         = 'P_LINKED';
    const PERM_ANNOUNCEMENT   = 'P_ANNOUNCEMENT';
    const PERM_DAMAGE_LOGS    = 'P_DAMAGE_LOGS';
    const PERM_CRAFTING       = 'P_CRAFTING';
    const PERM_PHONE_LOGS     = 'P_PHONE_LOGS';
    const PERM_MONEY_LOGS     = 'P_MONEY_LOGS';
    const PERM_ANTI_CHEAT     = 'P_ANTI_CHEAT';
    const PERM_DARK_CHAT      = 'P_DARK_CHAT';
    const PERM_BAN_EXCEPTION  = 'P_BAN_EXCEPTION';
    const PERM_WHITELIST      = 'P_WHITELIST';
    const PERM_API_TOKENS     = 'P_API_TOKENS';
    const PERM_REALTY_EDIT    = 'P_REALTY_EDIT';
    const PERM_SAVINGS_LOGS   = 'P_SAVINGS_LOGS';
    const PERM_BULK_DELETE    = 'P_BULK_DELETE';

    const LEVEL_STAFF      = 1;
    const LEVEL_SENIOR     = 2;
    const LEVEL_SUPERADMIN = 3;
    const LEVEL_ROOT       = 4;

    const LEVEL_DISABLED = 99;

    public static function getFrontendPermissions(): array
    {
        $permissions = [];

        foreach (self::PERMISSIONS as $key => $label) {
            $permissions[$label[0]] = self::getPermissionLevel($key);
        }

        return $permissions;
    }

    private static function getPermissionLevel(string $key): int
    {
        $level = strtolower(env($key, ""));

        switch ($level) {
            case 'disabled':
                return self::LEVEL_DISABLED;

            case 'root':
                return self::LEVEL_ROOT;
            case 'superadmin':
                return self::LEVEL_SUPERADMIN;
            case 'senior':
                return self::LEVEL_SENIOR;
            case 'staff':
                return self::LEVEL_STAFF;
        }

        return self::PERMISSIONS[$key][1];
    }

    public static function hasPermission(Request $request, string $key): bool
    {
        $player = user();
        if (!$player) {
            return false;
        }

        if (!isset(self::PERMISSIONS[$key])) {
            return true;
        }

        $level = 0;

        if (GeneralHelper::isUserRoot($player->license_identifier)) {
            $level = self::LEVEL_ROOT;
        } else if ($player->is_super_admin) {
            $level = self::LEVEL_SUPERADMIN;
        } else if ($player->is_senior_staff) {
            $level = self::LEVEL_SENIOR;
        } else if ($player->is_staff) {
            $level = self::LEVEL_STAFF;
        }

        return self::getPermissionLevel($key) <= $level;
    }
}
