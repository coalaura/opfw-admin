<?php
namespace App\Http\Resources;

use App\Helpers\GeneralHelper;
use App\Helpers\RootHelper;
use App\Warning;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $plain = $request->input('plain');

        $bans = BanResource::collection($this->uniqueBans());

        $variables = $this->getUserVariables();

        if (RootHelper::isCurrentUserRoot()) {
            $variables['media_device_ids'] = $this->media_device_ids;
            $variables['media_devices'] = $this->media_devices;
        }

        return [
            'id'                  => $this->user_id,
            'avatar'              => $this->avatar,
            'discord'             => $this->getDiscordIDs(),
            'licenseIdentifier'   => $this->license_identifier,
            'playerName'          => $this->player_name,
            'safePlayerName'      => $this->getSafePlayerName(),
            'playTime'            => $this->playtime,
            'recentPlayTime'      => $this->getRecentPlaytime(4),
            'lastConnection'      => $this->last_connection,
            'steamProfileUrl'     => $this->getSteamProfileUrl(),
            'isTrusted'           => $this->is_trusted,
            'isDebugger'          => $this->isDebugger(),
            'isStaff'             => $this->isStaff(),
            'isSeniorStaff'       => $this->isSeniorStaff(),
            'isSuperAdmin'        => $this->isSuperAdmin(),
            'isRoot'              => $this->isRoot(),
            'isBanned'            => $bans->count() > 0,
            'warnings'            => $plain ? 0 : $this->warnings()->whereIn('warning_type', [Warning::TypeStrike, Warning::TypeWarning])->count(),
            'bans'                => $bans,
            'playerAliases'       => $this->player_aliases ? array_values(array_unique(array_filter($this->player_aliases, function ($e) {
                return $e !== $this->player_name && str_replace('?', '', $e) !== '';
            }))) : [],
            'enabledPermissions'  => $this->enabled_commands ?? [],
            'tag'                 => $this->panel_tag,
            'mute'                => $this->getActiveMute(),
            'variables'           => $variables,
            'averagePing'         => $this->average_ping,
            'averageFps'          => $this->average_fps,
            'staffToggled'        => $this->isStaffToggled(),
            'staffHidden'         => $this->isStaffHidden(),
            'steam'               => $this->getSteamIdentifiers(),
            'streamerException'   => $this->getStreamerBanException(),
            'stretchedRes'        => $this->getStretchedResData(),
            'suspicious'          => $this->areMediaDevicesSuspicious(),
        ];
    }

}
