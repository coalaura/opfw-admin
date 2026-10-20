<?php
namespace App\Helpers;

use App\Player;

class PermissionHelper
{
    const PERMISSIONS = [
        self::PERM_SCREENSHOT           => ['screenshot', self::LEVEL_STAFF],
        self::PERM_SCREENSHOT_SHELL     => ['screenshot_shell', self::LEVEL_SENIOR],
        self::PERM_SUSPICIOUS           => ['suspicious', self::LEVEL_SENIOR],
        self::PERM_ADVANCED             => ['advanced', self::LEVEL_SENIOR],
        self::PERM_LIVEMAP              => ['livemap', self::LEVEL_STAFF],
        self::PERM_LOCK_BAN             => ['lock_ban', self::LEVEL_SENIOR],
        self::PERM_EDIT_TAG             => ['edit_tag', self::LEVEL_ROOT],
        self::PERM_LOADING_SCREEN       => ['loading_screen', self::LEVEL_SUPERADMIN],
        self::PERM_VIEW_QUEUE           => ['view_queue', self::LEVEL_SENIOR],
        self::PERM_Y_DELETE             => ['y_delete', self::LEVEL_SUPERADMIN],
        self::PERM_Y_VERIFY             => ['y_verify', self::LEVEL_SUPERADMIN],
        self::PERM_Y_EDIT               => ['y_edit', self::LEVEL_SUPERADMIN],
        self::PERM_LINKED               => ['linked', self::LEVEL_ROOT],
        self::PERM_LINKED_VALUES        => ['linked_values', self::LEVEL_ROOT],
        self::PERM_ANNOUNCEMENT         => ['announcement', self::LEVEL_SUPERADMIN],
        self::PERM_DAMAGE_LOGS          => ['damage_logs', self::LEVEL_SENIOR],
        self::PERM_EXPLOSION_EVENTS     => ['explosion_events', self::LEVEL_STAFF],
        self::PERM_CRAFTING             => ['crafting', self::LEVEL_SUPERADMIN],
        self::PERM_PHONE_LOGS           => ['phone_logs', self::LEVEL_SUPERADMIN],
        self::PERM_MONEY_LOGS           => ['money_logs', self::LEVEL_STAFF],
        self::PERM_ANTI_CHEAT           => ['anti_cheat', self::LEVEL_STAFF],
        self::PERM_DARK_CHAT            => ['dark_chat', self::LEVEL_SENIOR],
        self::PERM_BAN_EXCEPTION        => ['ban_exception', self::LEVEL_SUPERADMIN],
        self::PERM_WHITELIST            => ['whitelist', self::LEVEL_SUPERADMIN],
        self::PERM_API_TOKENS           => ['api_tokens', self::LEVEL_SUPERADMIN],
        self::PERM_REALTY_EDIT          => ['realty_edit', self::LEVEL_SUPERADMIN],
        self::PERM_REALTY_VIEW          => ['realty_view', self::LEVEL_SENIOR],
        self::PERM_SAVINGS_LOGS         => ['savings_logs', self::LEVEL_SENIOR],
        self::PERM_BULK_DELETE          => ['bulk_delete', self::LEVEL_ROOT],
        self::PERM_VIEW_INVENTORY       => ['view_inventory', self::LEVEL_SUPERADMIN],
        self::PERM_EDIT_INVENTORY       => ['edit_inventory', self::LEVEL_SUPERADMIN],
        self::PERM_DIVORCE_CHARACTERS   => ['divorce_characters', self::LEVEL_SUPERADMIN],
        self::PERM_REVIVE_OFFLINE       => ['revive_offline', self::LEVEL_SUPERADMIN],
        self::PERM_MANAGE_VEHICLES      => ['manage_vehicles', self::LEVEL_SUPERADMIN],
        self::PERM_EDIT_PED_MODEL       => ['edit_ped_model', self::LEVEL_SUPERADMIN],
        self::PERM_RESET_VEHICLE_GARAGE => ['reset_vehicle_garage', self::LEVEL_SUPERADMIN],
        self::PERM_SUSPICIOUS_ENTITIES  => ['suspicious_entities', self::LEVEL_SENIOR],
        self::PERM_EDIT_COMPANY_BALANCE => ['edit_company_balance', self::LEVEL_SUPERADMIN],
        self::PERM_EDIT_SAVINGS_BALANCE => ['edit_savings_balance', self::LEVEL_SUPERADMIN],
        self::PERM_VIEW_AUDIT_LOGS      => ['view_audit_logs', self::LEVEL_SENIOR],
        self::PERM_CONTAINER_ACCESS    => ['container_access', self::LEVEL_SENIOR],
        self::PERM_HIDDEN_NOTES        => ['hidden_notes', self::LEVEL_SENIOR],
        self::PERM_RESTRICTED_LOGS     => ['restricted_logs', self::LEVEL_SENIOR],
        self::PERM_USER_VARIABLES      => ['user_variables', self::LEVEL_SENIOR],
        self::PERM_BLACKLIST           => ['blacklist', self::LEVEL_SUPERADMIN],
        self::PERM_DELETE_CHARACTERS   => ['delete_characters', self::LEVEL_SUPERADMIN],
        self::PERM_EDIT_CHARACTER_BALANCE => ['edit_character_balance', self::LEVEL_SUPERADMIN],
        self::PERM_DIAGNOSTICS         => ['diagnostics', self::LEVEL_ROOT],
    ];

    const PERM_SCREENSHOT           = 'P_SCREENSHOT';
    const PERM_SCREENSHOT_SHELL     = 'P_SCREENSHOT_SHELL';
    const PERM_SUSPICIOUS           = 'P_SUSPICIOUS';
    const PERM_ADVANCED             = 'P_ADVANCED';
    const PERM_LIVEMAP              = 'P_LIVEMAP';
    const PERM_LOCK_BAN             = 'P_LOCK_BAN';
    const PERM_EDIT_TAG             = 'P_EDIT_TAG';
    const PERM_LOADING_SCREEN       = 'P_LOADING_SCREEN';
    const PERM_VIEW_QUEUE           = 'P_VIEW_QUEUE';
    const PERM_Y_DELETE             = 'P_Y_DELETE';
    const PERM_Y_VERIFY             = 'P_Y_VERIFY';
    const PERM_Y_EDIT               = 'P_Y_EDIT';
    const PERM_LINKED               = 'P_LINKED';
    const PERM_LINKED_VALUES        = 'P_LINKED_VALUES';
    const PERM_ANNOUNCEMENT         = 'P_ANNOUNCEMENT';
    const PERM_DAMAGE_LOGS          = 'P_DAMAGE_LOGS';
    const PERM_EXPLOSION_EVENTS     = 'P_EXPLOSION_EVENTS';
    const PERM_CRAFTING             = 'P_CRAFTING';
    const PERM_PHONE_LOGS           = 'P_PHONE_LOGS';
    const PERM_MONEY_LOGS           = 'P_MONEY_LOGS';
    const PERM_ANTI_CHEAT           = 'P_ANTI_CHEAT';
    const PERM_DARK_CHAT            = 'P_DARK_CHAT';
    const PERM_BAN_EXCEPTION        = 'P_BAN_EXCEPTION';
    const PERM_WHITELIST            = 'P_WHITELIST';
    const PERM_API_TOKENS           = 'P_API_TOKENS';
    const PERM_REALTY_EDIT          = 'P_REALTY_EDIT';
    const PERM_REALTY_VIEW          = 'P_REALTY_VIEW';
    const PERM_SAVINGS_LOGS         = 'P_SAVINGS_LOGS';
    const PERM_BULK_DELETE          = 'P_BULK_DELETE';
    const PERM_VIEW_INVENTORY       = 'P_VIEW_INVENTORY';
    const PERM_EDIT_INVENTORY       = 'P_EDIT_INVENTORY';
    const PERM_DIVORCE_CHARACTERS   = 'P_DIVORCE_CHARACTERS';
    const PERM_REVIVE_OFFLINE       = 'P_REVIVE_OFFLINE';
    const PERM_MANAGE_VEHICLES      = 'P_MANAGE_VEHICLES';
    const PERM_EDIT_PED_MODEL       = 'P_EDIT_PED_MODEL';
    const PERM_RESET_VEHICLE_GARAGE = 'P_RESET_VEHICLE_GARAGE';
    const PERM_SUSPICIOUS_ENTITIES  = 'P_SUSPICIOUS_ENTITIES';
    const PERM_EDIT_COMPANY_BALANCE = 'P_EDIT_COMPANY_BALANCE';
    const PERM_EDIT_SAVINGS_BALANCE = 'P_EDIT_SAVINGS_BALANCE';
    const PERM_VIEW_AUDIT_LOGS      = 'P_VIEW_AUDIT_LOGS';
    const PERM_CONTAINER_ACCESS    = 'P_CONTAINER_ACCESS';
    const PERM_HIDDEN_NOTES        = 'P_HIDDEN_NOTES';
    const PERM_RESTRICTED_LOGS     = 'P_RESTRICTED_LOGS';
    const PERM_USER_VARIABLES      = 'P_USER_VARIABLES';
    const PERM_BLACKLIST           = 'P_BLACKLIST';
    const PERM_DELETE_CHARACTERS   = 'P_DELETE_CHARACTERS';
    const PERM_EDIT_CHARACTER_BALANCE = 'P_EDIT_CHARACTER_BALANCE';
    const PERM_DIAGNOSTICS         = 'P_DIAGNOSTICS';

    // Access to a stronger permission also grants its base permission.
    const INHERITED_FROM = [
        self::PERM_SCREENSHOT => [self::PERM_SCREENSHOT_SHELL],
        self::PERM_Y_DELETE => [self::PERM_Y_EDIT],
        self::PERM_REALTY_VIEW => [self::PERM_REALTY_EDIT],
        self::PERM_VIEW_INVENTORY => [self::PERM_EDIT_INVENTORY],
    ];

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

    public static function hasPermission(string $key): bool
    {
        $player = user();
        if (! $player) {
            return false;
        }

        return self::playerHasPermission($player, $key);
    }

    public static function playerHasPermission(Player $player, string $key): bool
    {
        if (!isset(self::PERMISSIONS[$key]) || !$player->isStaff()) {
            return false;
        }

        $requiredLevel = self::getPermissionLevel($key);

        if ($requiredLevel === self::LEVEL_DISABLED) {
            return false;
        }

        if ($requiredLevel <= self::getPlayerLevel($player)) {
            return true;
        }

        if (in_array(self::PERMISSIONS[$key][0], self::getPlayerPermissions($player), true)) {
            return true;
        }

        foreach (self::INHERITED_FROM[$key] ?? [] as $source) {
            if (self::playerHasPermission($player, $source)) {
                return true;
            }
        }

        return false;
    }

    public static function getPlayerPermissions(Player $player): array
    {
        $permissions = $player->panel_permissions ?? [];

        // Normalize grants saved before the Y deletion permission was renamed.
        if (in_array('y', $permissions, true)) {
            foreach ($permissions as $index => $permission) {
                if ($permission === 'y') {
                    $permissions[$index] = 'y_delete';
                }
            }

            $permissions = array_values(array_unique($permissions));
        }

        return $permissions;
    }

    public static function getPlayerLevel(Player $player): int
    {
        if ($player->isRoot()) {
            return self::LEVEL_ROOT;
        }

        if ($player->isSuperAdmin()) {
            return self::LEVEL_SUPERADMIN;
        }

        if ($player->isSeniorStaff()) {
            return self::LEVEL_SENIOR;
        }

        return $player->isStaff() ? self::LEVEL_STAFF : 0;
    }

    public static function getGrantablePermissions(Player $player): array
    {
        if (!$player->isSuperAdmin()) {
            return [];
        }

        $level = self::getPlayerLevel($player);

        return array_keys(array_filter(self::getFrontendPermissions(), function ($requiredLevel) use ($level) {
            return $requiredLevel <= $level;
        }));
    }

    public static function getFrontendAccess(): array
    {
        $access = [];

        foreach (self::PERMISSIONS as $key => $permission) {
            $access[$permission[0]] = self::hasPermission($key);
        }

        return $access;
    }

    public static function getFrontendInheritance(): array
    {
        $inheritance = [];

        foreach (self::INHERITED_FROM as $key => $sources) {
            $inheritance[self::PERMISSIONS[$key][0]] = array_map(function ($source) {
                return self::PERMISSIONS[$source][0];
            }, $sources);
        }

        return $inheritance;
    }
}
