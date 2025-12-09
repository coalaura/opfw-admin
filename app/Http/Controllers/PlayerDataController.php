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
        "advanced_metagame", "auto_drive", "battle_royale_toggle", "brighter_nights", "cam_point",
        "cpr", "create_garage", "door_debug", "fortnite", "freecam", "garage_vehicle", "idle", "indestructibility",
        "invisibility", "keychain_create", "live_map", "minecraft", "noclip", "pickup_keys", "player_stats",
        "range_revive", "reflect", "register_weapon", "registration_lookup", "remove_garage", "respawn_vehicle",
        "set_body_armor", "set_fuel", "slap", "snapshot", "stable_cam", "super_jump", "ungarage_vehicle",
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
        $user   = user();
        $status = ! ! $request->input('status');

        if ($status) {
            $reason  = $request->input('reason');
            $expires = $request->input('expires');

            if (empty($reason) || empty($expires) || strtotime($expires) > time()) {
                return backWith('error', 'You need to provide a reason and expiration date.');
            }

            $mute = $player->getActiveMute();

            if ($mute) {
                return backWith('error', 'Player is already muted.');
            }

            $player->setUserData('muted', [
                'reason'          => $reason,
                'expiryTimestamp' => $expires,
                'creatorName'     => user()->player_name,
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
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_WHITELIST)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $user = user();

        $whitelisted = DB::table('user_whitelist')
            ->select(['license_identifier'])
            ->where('license_identifier', '=', $player->license_identifier)
            ->exists();

        $status = $request->input('status');

        if ($status) {
            if (! $whitelisted) {
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
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_BAN_EXCEPTION)) {
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
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_EDIT_TAG)) {
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
        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'You dont have permissions to do this.');
        }

        $user = user();

        $enabledCommands = $request->input('enabledCommands');

        $enabledCommands = array_values(array_unique(array_filter($enabledCommands, function($command) {
            return in_array($command, self::EnablableCommands);
        })));

        $currentEnabled  = $player->enabled_commands ?? [];

        if (! empty($enabledCommands) && empty(array_diff($enabledCommands, $currentEnabled))) {
            return backWith('success', 'No commands changed.');
        }

        $player->update([
            "enabled_commands" => $enabledCommands,
        ]);

        $license = $player->license_identifier;
        $status  = StatusHelper::get($license);

        $refreshed = "";

        if ($status) {
            $response = ServerAPI::refreshUser($status['server'], $license);

            if ($response) {
                $refreshed = ' Refreshed loaded user successfully.';
            } else {
                $refreshed = ' Failed to refresh loaded user.';
            }
        }

        PanelLog::log(
            $user->license_identifier,
            "Edited Commands",
            sprintf("%s edited the enabled commands of %s.", $user->consoleName(), $player->consoleName()),
            ['commands' => $enabledCommands]
        );

        return backWith('success', 'Commands have been updated successfully.' . $refreshed);
    }

    /**
     * Returns all user notifications.
     *
     * @param Player $player
     * @param Request $request
     * @return Response
     */
    public function notifications(Player $player)
    {
        $notifications = DB::table("user_notifications")->select([
            "id", "creator_identifier", "users.player_name", "notification", "created_at", "read_at",
        ])->leftJoin("users", "users.license_identifier", "user_notifications.creator_identifier")->where("user_notifications.license_identifier", $player->license_identifier)->orderByDesc("created_at")->get()->toArray();

        return $this->json(true, array_map(function ($notification) {
            $notification->player_name = Player::getFilteredPlayerName($notification->player_name, [], $notification->creator_identifier);

            return $notification;
        }, $notifications));
    }

    /**
     * Creates a user notification
     *
     * @param Player $player
     * @param Request $request
     * @return RedirectResponse
     */
    public function createNotification(Player $player, Request $request)
    {
        $user = user();

        $notification = trim($request->input("notification") ?? "");

        if (empty($notification) || strlen($notification) > 2000) {
            return $this->json(false, null, "invalid notification body");
        }

        $id = DB::table("user_notifications")->insertGetId([
            "license_identifier" => $player->license_identifier,
            "creator_identifier" => $user->license_identifier,
            "notification"       => $notification,
            "created_at"         => time(),
        ]);

        if (! $id) {
            return $this->json(false, null, "failed to create notification");
        }

        $status = StatusHelper::get($player->license_identifier);

        if ($status) {
            ServerAPI::showUserNotifications($status['server'], $status['source']);
        }

        PanelLog::log(
            $user->license_identifier,
            "Created Notification",
            sprintf("%s created notification #%d for %s.", $user->consoleName(), $id, $player->consoleName()),
            ['notification' => $notification]
        );

        return $this->notifications($player);
    }

    /**
     * Deletes a user notification
     *
     * @param Player $player
     * @return RedirectResponse
     */
    public function deleteNotification(Player $player, int $id)
    {
        if (! $id || $id <= 0) {
            return $this->json(false, null, "invalid notification");
        }

        $user = user();

        $notification = DB::table("user_notifications")->where("license_identifier", $player->license_identifier)->where("id", $id)->first();

        if (! $notification) {
            return $this->json(false, null, "notification not found");
        } else if ($notification->read_at) {
            return $this->json(false, null, "notification was already read");
        }

        DB::table("user_notifications")->where("license_identifier", $player->license_identifier)->where("id", $id)->delete();

        PanelLog::log(
            $user->license_identifier,
            "Deleted Notification",
            sprintf("%s deleted notification #%d for %s.", $user->consoleName(), $id, $player->consoleName()),
            ['notification' => $notification->notification]
        );

        return $this->notifications($player);
    }
}
