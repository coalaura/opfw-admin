<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerDataController extends Controller
{
    const EnablableCommands = [
        "cam_clear",
        "cam_play",
        "cam_point",
        "disable_idle_cam",
        "freecam",
        "orbitcam",
        "player_stats",
    ];

    /**
     * Sets the whitelist status
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateWhitelistStatus(Player $player, Request $request): RedirectResponse
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_WHITELIST)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $whitelisted = DB::table('user_whitelist')
            ->select(['license_identifier'])
            ->where('license_identifier', '=', $player->license_identifier)
            ->exists();

        $status = $request->input('status');

        if ($status) {
            if (!$whitelisted) {
                DB::table('user_whitelist')->insert([
                    'license_identifier' => $player->license_identifier,
                ]);
            }
        } else {
            if ($whitelisted) {
                DB::table('user_whitelist')->where('license_identifier', '=', $player->license_identifier)->delete();
            }
        }

        return backWith('success', 'Whitelist status has been updated successfully.');
    }

    /**
     * Sets the ban exception status
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateBanExceptionStatus(Player $player, Request $request): RedirectResponse
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_BAN_EXCEPTION)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $twitch = $request->input('twitch');
        $twitch = is_string($twitch) ? preg_replace('/[^a-zA-Z0-9_]/', '', $twitch) : false;

        $data = $player->user_data ?? [];

        if (empty($twitch)) {
            unset($data['twitchBanException']);
        } else {
            $data['twitchBanException'] = $twitch;
        }

        $player->update([
            'user_data' => $data,
        ]);

        return backWith('success', 'Ban exception status has been updated successfully.');
    }

    /**
     * Sets the soft ban status
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSoftBanStatus(Player $player, Request $request): RedirectResponse
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_SOFT_BAN)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $status = $request->input('status') ? 1 : 0;

        $player->update([
            'is_soft_banned' => $status,
        ]);

        return backWith('success', 'Soft ban status has been updated successfully.');
    }

    /**
     * Sets the tag
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateTag(Player $player, Request $request): RedirectResponse
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_EDIT_TAG)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $tag = $request->input('tag') ? trim($request->input('tag')) : null;

        $player->update([
            'panel_tag' => $tag,
        ]);

        Player::resolveTags(true);

        return backWith('success', 'Tag has been updated successfully.');
    }

    /**
     * Updates the role
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateRole(Player $player, Request $request): RedirectResponse
    {
        if (!env('ALLOW_ROLE_EDITING', false) || !$this->isSuperAdmin($request)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $role = $request->input('role') ? trim($request->input('role')) : null;

        $data = [
            'is_trusted'      => 0,
            'is_staff'        => 0,
            'is_senior_staff' => 0,
        ];

        if ($role === 'seniorStaff') {
            $data['is_senior_staff'] = 1;
        } else if ($role === 'staff') {
            $data['is_staff'] = 1;
        } else if ($role === 'trusted') {
            $data['is_trusted'] = 1;
        }

        $player->update($data);

        return backWith('success', 'Role has been updated successfully.');
    }

    /**
     * Updates enabled commands
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateEnabledCommands(Player $player, Request $request): RedirectResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $enabledCommands = $request->input('enabledCommands');

        foreach ($enabledCommands as $command) {
            if (!in_array($command, self::EnablableCommands)) {
                return backWith('error', 'You cannot enable the command "' . $command . '".');
            }
        }

        $player->update([
            "enabled_commands" => $enabledCommands,
        ]);

        return backWith('success', 'Commands have been updated successfully.');
    }
}
