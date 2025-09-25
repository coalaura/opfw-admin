<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\Helpers\StatusHelper;
use App\PanelLog;
use App\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerDataController extends Controller
{
    const EnablableCommands = [
        "advanced_metagame",
        "auto_drive",
        "brighter_nights",
        "cam_clear",
        "cam_play",
        "cam_point",
        "cpr",
        "fortnite",
        "freecam",
        "live_map",
        "minecraft",
        "orbitcam",
        "player_stats",
        "reflect",
        "register_weapon",
        "stable_cam",
        "super_jump",
        "watermark",
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
        $user = user();
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

            PanelLog::log(
                $user->license_identifier,
                "Muted Player",
                sprintf("%s muted %s.", $user->consoleName(), $player->consoleName()),
                ['reason' => $reason]
            );
        } else {
            $player->setUserData('muted', null);

            PanelLog::log(
                $user->license_identifier,
                "Unmuted Player",
                sprintf("%s unmuted %s.", $user->consoleName(), $player->consoleName()),
            );
        }

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
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_WHITELIST)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $user = user();

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

            PanelLog::log(
                $user->license_identifier,
                "Whitelisted Player",
                sprintf("%s whitelisted %s.", $user->consoleName(), $player->consoleName()),
            );
        } else {
            if ($whitelisted) {
                DB::table('user_whitelist')->where('license_identifier', '=', $player->license_identifier)->delete();
            }

            PanelLog::log(
                $user->license_identifier,
                "Un-Whitelisted Player",
                sprintf("%s un-whitelisted %s.", $user->consoleName(), $player->consoleName()),
            );
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
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_BAN_EXCEPTION)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $user = user();

        $twitch = $request->input('twitch');
        $twitch = is_string($twitch) ? preg_replace('/[^a-zA-Z0-9_]/', '', $twitch) : false;

        $player->setUserData('twitchBanException', $twitch);

        if ($twitch) {
            PanelLog::log(
                $user->license_identifier,
                "Enabled Ban Exception",
                sprintf("%s enabled ban exception for %s (`%s`).", $user->consoleName(), $player->consoleName(), $twitch),
            );
        } else {
            PanelLog::log(
                $user->license_identifier,
                "Removed Ban Exception",
                sprintf("%s removed ban exception for %s.", $user->consoleName(), $player->consoleName(), $twitch),
            );
        }

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
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_EDIT_TAG)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $user = user();

        $tag = $request->input('tag') ? trim($request->input('tag')) : null;

        $player->update([
            'panel_tag' => $tag,
        ]);

        Player::resolveTags(true);

        if ($tag) {
            PanelLog::log(
                $user->license_identifier,
                "Updated Tag",
                sprintf("%s set the tag of %s to `%s`.", $user->consoleName(), $player->consoleName(), $tag),
            );
        } else {
            PanelLog::log(
                $user->license_identifier,
                "Removed Tag",
                sprintf("%s removed the tag of %s.", $user->consoleName(), $player->consoleName()),
            );
        }

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

        $user = user();

        $enabledCommands = $request->input('enabledCommands');

        foreach ($enabledCommands as $command) {
            if (!in_array($command, self::EnablableCommands)) {
                return backWith('error', 'You cannot enable the command "' . $command . '".');
            }
        }

        $enabledCommands = array_values(array_unique($enabledCommands));
        $currentEnabled = $player->enabled_commands ?? [];

        if (empty(array_diff($enabledCommands, $currentEnabled))) {
            return backWith('success', 'No commands changed.');
        }

        $player->update([
            "enabled_commands" => $enabledCommands,
        ]);

        // TODO
        /*
        $license = $player->license_identifier;
        $status = StatusHelper::get($license);

        $refreshed = "";

        if ($status) {
            $response = ServerAPI::refreshUser($status['server'], $license);

            if ($response) {
                $refreshed = ' Refreshed loaded user successfully.';
            } else {
                $refreshed = ' Failed to refresh loaded user.';
            }
        }
        */

        PanelLog::log(
            $user->license_identifier,
            "Edited Commands",
            sprintf("%s edited the enabled commands of %s.", $user->consoleName(), $player->consoleName()),
            ['commands' => $enabledCommands]
        );

        return backWith('success', 'Commands have been updated successfully.'/* . $refreshed*/);
    }
}
