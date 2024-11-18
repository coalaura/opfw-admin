<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\PanelLog;
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
     * Sets the mute status
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateMuteStatus(Player $player, Request $request): RedirectResponse
    {
        $status = !!$request->input('status');

        if ($status) {
            $reason = $request->input('reason');
            $expires = $request->input('expires');

            if (empty($reason) || empty($expires) || strtotime($expires) > time()) {
                return backWith('error', 'You need to provide a reason and expiration date.');
            }

            $mute = $player->getActiveMute();

            if ($mute) {
                return backWith('error', 'Player is already muted.');
            }

            $player->setUserData('muted', [
                'reason'  => $reason,
                'expiryTimestamp' => $expires,
                'creatorName' => user()->player_name,
            ]);
        } else {
            $player->setUserData('muted', null);
        }

        PanelLog::logMuteUpdate(license(), $player->license_identifier, $status);

        return backWith('success', 'Muted status has been updated successfully.');
    }

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

        $player->setUserData('twitchBanException', $twitch);

        PanelLog::logBanExceptionUpdate(license(), $player->license_identifier, $twitch ?? null);

        return backWith('success', 'Ban exception status has been updated successfully.');
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
