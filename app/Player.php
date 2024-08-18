<?php

namespace App;

use App\Helpers\CacheHelper;
use App\Helpers\GeneralHelper;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SteamID;

/**
 * @package App
 */
class Player extends Model
{
    use HasFactory;

    /**
     * The link used for Steam's new invite code.
     */
    const STEAM_INVITE_URL = 'http://s.team/p/';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Whether to use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'license_identifier',
        'player_name',
        'player_aliases',
        'identifiers',
        'last_used_identifiers',
        'ips',
        'player_tokens',
        'user_variables',
        'is_staff',
        'is_senior_staff',
        'is_super_admin',
        'is_trusted',
        'is_debugger',
        'is_soft_banned',
        'panel_drug_department',
        'is_soft_banned',
        'playtime',
        'total_joins',
        'priority_level',
        'last_connection',
        'enabled_commands',
        'panel_tag',
        'average_ping',
        'average_fps',
        'panel_settings',
        'staff_points',
        'user_data',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'identifiers'           => 'array',
        'last_used_identifiers' => 'array',
        'player_tokens'         => 'array',
        'ips'                   => 'array',
        'user_variables'        => 'array',
        'player_aliases'        => 'array',
        'enabled_commands'      => 'array',
        'user_data'             => 'array',
        'staff_points'             => 'array',
        'last_connection'       => 'datetime',
        'is_trusted'            => 'boolean',
        'is_staff'              => 'boolean',
        'is_super_admin'        => 'boolean',
        'is_debugger'           => 'boolean',
        'panel_drug_department' => 'boolean',
        'is_soft_banned'        => 'boolean',
        'playtime'              => 'integer',
        'total_joins'           => 'integer',
        'priority_level'        => 'integer',
        'panel_settings'        => 'array',
        'weekly_playtime'       => 'array',
    ];

    /**
     * Cached active ban.
     *
     * @var ?Ban
     */
    private $ban = false;

    const PlayerSettings = [
        "banner"          => [
            "type"    => "url",
            "default" => "",
            "presets" => [
                "Blossom" => "/images/themes/blossom.jpg",
                "Cat"     => "/images/themes/cat.jpg",
                "Coffee"  => "/images/themes/coffee.jpg",
                "Desert"  => "/images/themes/desert.jpg",
                "Forest"  => "/images/themes/forest.jpg",
                "Space"   => "/images/themes/space.jpg",
                "Sunrise" => "/images/themes/sunrise.jpg",
            ],
        ],
        "bannerAlpha"     => [
            "type"    => "boolean",
            "default" => false,
        ],
        "parseLogs"       => [
            "type"    => "boolean",
            "default" => true,
        ],
        "expandCollapsed" => [
            "type"    => "boolean",
            "default" => false,
        ],
        "expandSidenav"   => [
            "type"    => "boolean",
            "default" => false,
        ],
        "showSystemNotes" => [
            "type"    => "boolean",
            "default" => false,
        ],
        "relativeTime" => [
            "type"    => "boolean",
            "default" => false,
        ],
        "locale"          => [
            "type"    => "string",
            "default" => "en-us",
            "options" => [
                "en-us"     => "English",
                "no"        => "Norwegian",
                "hi-lat"    => "Hindi",

                "en-cave"   => "Caveman Speak",
                "en-uwu"    => "UwU Language",
                "en-pirate" => "Pirate Speak",
                "en-us_s"   => "Southern Accent",
                "en-emoji"  => "Emojis",
            ],
        ],
    ];

    const PlayerNameFilter = [
        "%^0",
        "%^1",
        "%^2",
        "%^3",
        "%^4",
        "%^5",
        "%^6",
        "%^7",
        "%^8",
        "%^9",
        "~a~",
        "~b~",
        "~c~",
        "~d~",
        "~e~",
        "~f~",
        "~g~",
        "~h~",
        "~i~",
        "~j~",
        "~k~",
        "~l~",
        "~m~",
        "~n~",
        "~o~",
        "~p~",
        "~q~",
        "~r~",
        "~s~",
        "~t~",
        "~u~",
        "~v~",
        "~w~",
        "~x~",
        "~y~",
        "~z~",
        "~italic~",
        "~bold~",
    ];

    public function setPanelSetting(string $key, $value, ?callable $progress = null)
    {
        $info = self::PlayerSettings[$key] ?? null;

        if (!$info) {
            throw new Exception('Invalid settings key: ' . $key);
        }

        $settings = $this->panel_settings ?? [];

        $expected = $info['type'];

        if ($expected === 'url') {
            if (!$value) {
                $value = '';
            }

            $previous = $settings[$key] ?? null;

            $presets = $info['presets'] ?? [];

            $isPreset  = in_array($value, $presets);
            $wasPreset = in_array($previous, $presets);

            // We only need to validate the URL if it's not a preset
            if ($value !== '' && !$isPreset) {
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new Exception('Input is not a valid URL.');
                }

                if (!preg_match('/^https:\/\/[^\s?]+?\.(png|jpe?g|webp)(\?[^\s]*)?$/mi', $value)) {
                    throw new Exception('URL is not a valid image.');
                }

                $url = $value;

                $path = explode('?', $url)[0];
                $ext  = explode('.', $path);
                $ext  = strtolower(end($ext));

                // We convert the image to a webp after resizing it
                $value = '/_uploads/' . md5(strtolower($path)) . '.webp';

                if ($previous === $value) {
                    return;
                }

                $progress && $progress("download");

                $data = GeneralHelper::get($url, 'image/*');

                if (!$data) {
                    // Imgur is blocking pretty hard for some reason... :(
                    if (Str::startsWith($url, 'https://i.imgur.com/')) {
                        throw new Exception('Imgur is blocking the download of this image.');
                    }

                    throw new Exception('Failed to download image.');
                }

                $dir = public_path('/_uploads/');
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

                $progress && $progress("resize");

                GeneralHelper::renderThumbnail(public_path($value), $data, 1280, 720, true);
            }

            if ($previous && !$wasPreset && preg_match('/^\/_uploads\/[a-f0-9]+\.(png|jpe?g|webp)$/mi', $previous)) {
                $file = public_path($previous);

                if (file_exists($file)) {
                    unlink($file);
                }
            }
        } else if (gettype($value) !== $expected) {
            throw new Exception('Invalid input type, expected ' . $expected . '.');
        }

        if (isset($info['options']) && !isset($info['options'][$value])) {
            throw new Exception('Input is not a valid option.');
        }

        $progress && $progress("save");

        $settings[$key] = $value;

        $this->update([
            'panel_settings' => $settings,
        ]);
    }

    public function getPanelSetting(string $key)
    {
        $settings = $this->panel_settings ?? [];

        $setting = self::PlayerSettings[$key] ?? null;

        if (!$setting) {
            return null;
        }

        $value = $settings[$key] ?? $setting['default'];
        if ($value && preg_match('/^\/_uploads\/[a-f0-9]+\.(png|jpe?g|webp)$/mi', $value)) {
            $file = public_path($value);

            if (!file_exists($file)) {
                $value = '';
            }
        }

        return $value;
    }

    public function getPanelSettings(): array
    {
        $list = [];

        foreach (self::PlayerSettings as $key => $setting) {
            $type = $setting['type'];

            $entry = [
                'value'    => $this->getPanelSetting($key),
                'type'     => $type,
                'options'  => $setting['options'] ?? false,
                'presets'  => $setting['presets'] ?? false,

                'disabled' => false,
                'focus'    => false,
            ];

            $list[$key] = $entry;
        }

        return $list;
    }

    public function isStaffToggled(): bool
    {
        $data = $this->user_data ?? [];

        return isset($data['staffToggled']) && $data['staffToggled'];
    }

    public function isStaffHidden(): bool
    {
        $data = $this->user_data ?? [];

        return isset($data['staffHidden']) && $data['staffHidden'];
    }

    public function getStreamerBanException(): ?string
    {
        $data = $this->user_data ?? [];

        return isset($data['twitchBanException']) ? $data['twitchBanException'] : null;
    }

    public function getStretchedResData(): ?array
    {
        $data = $this->user_data ?? [];

        return isset($data['stretchedRes']) ? $data['stretchedRes'] : null;
    }

    private static function filterPlayerName(string $name): string
    {
        foreach (self::PlayerNameFilter as $filter) {
            $name = str_ireplace($filter, '', $name);
        }

        $name = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $name);

        return trim($name);
    }

    public static function getFilteredPlayerName(string $name, $alias, string $license): string
    {
        $name  = self::filterPlayerName($name);
        $alias = $alias ?? [];

        if (is_string($alias)) {
            $alias = json_decode($alias, true) ?? [];
        }

        if ($name) {
            return $name;
        }

        for ($i = sizeof($alias) - 1; $i >= 0; $i--) {
            $name = self::filterPlayerName($alias[$i]);

            if ($name) {
                return $name;
            }
        }

        return substr($license, 8, 10);
    }

    public function getSafePlayerName(): string
    {
        return self::getFilteredPlayerName($this->player_name ?? "", $this->player_aliases, $this->license_identifier);
    }

    public static function findByDiscordId(string $id)
    {
        if (!preg_match("/^\d+$/m", $id)) {
            return null;
        }

        $found = self::query()
            ->select(["license_identifier", "player_name"])
            ->where(DB::raw("JSON_CONTAINS(last_used_identifiers, '\"discord:$id\"')"), '=', '1')
            ->orderByDesc("last_connection")
            ->get();

        if (!empty($found)) {
            return $found;
        }

        return self::query()
            ->select(["license_identifier", "player_name"])
            ->where(DB::raw("JSON_CONTAINS(identifiers, '\"discord:$id\"')"), '=', '1')
            ->orderByDesc("last_connection")
            ->get();
    }

    public function getRecentPlaytime(int $weeks): int
    {
        $after    = op_week_identifier() - $weeks;
        $playtime = 0;

        $weeklyPlaytime = $this->weekly_playtime ?? [];

        foreach ($weeklyPlaytime as $week => $time) {
            $week = intval($week);

            if ($week >= $after) {
                $playtime += $time;
            }
        }

        return $playtime;
    }

    public static function resolveTags(bool $refreshCache = false): array
    {
        if ($refreshCache || !CacheHelper::exists('tags')) {
            $tags = self::query()->select(['panel_tag'])->whereNotNull('panel_tag')->groupBy('panel_tag')->get()->toArray();

            CacheHelper::write('tags', $tags, CacheHelper::WEEK);
        }

        return CacheHelper::read('tags', []);
    }

    public function getActiveMute(): ?array
    {
        $data = $this->user_data ?? [];

        if (!isset($data['muted']) || !$data['muted']) {
            return null;
        }

        $mute = $data['muted'];

        if ($mute['expiryTimestamp'] && $mute['expiryTimestamp'] < time()) {
            return null;
        }

        return [
            'reason'  => $mute['reason'] ?? null,
            'expires' => $mute['expiryTimestamp'] ?? null,
            'creator' => $mute['creatorName'] ?? null,
        ];
    }

    public function setUserData(string $key, $value)
    {
        $data = $this->user_data ?? [];

        if ($value && !empty($value)) {
            $data[$key] = $value;
        } else {
            unset($data[$key]);
        }

        $this->update([
            'user_data' => $data,
        ]);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        // Steam Identifier
        if (Str::startsWith($value, 'steam:')) {
            return Player::query()->where('identifiers', 'LIKE', "%\"" . $value . "\"%")->get();
        }

        // License Identifier
        if (Str::startsWith($value, 'license:')) {
            return Player::query()->select()->where('license_identifier', '=', $value)->first();
        }

        // User ID hex
        if (Str::startsWith($value, '0x')) {
            $id = hexdec($value);
        } else {
            $id = intval($value);
        }

        if (!$id) {
            return null;
        }

        return Player::query()->select()->where('user_id', '=', $id)->first();
    }

    /**
     * Gets the route key name.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'license_identifier';
    }

    /**
     * @return string
     */
    public function getSteamIdentifier(): ?string
    {
        return $this->getIdentifier('steam') ?? null;
    }

    /**
     * Returns the discord user info (username, avatar, etc.)
     *
     * @return array|null
     */
    public function getDiscordInfo(): array
    {
        $ids = $this->getDiscordIDs();

        $last = $this->getLastUsedIdentifiers();

        $users = [];

        foreach ($ids as $id) {
            $user = DiscordUser::getUser($id);

            $info = $user ? $user->toArray() : null;

            if ($info && in_array('discord:' . $info['id'], $last)) {
                $info['linked'] = true;
            }

            $users[$id] = $info;
        }

        return $users;
    }

    /**
     * Returns the discord user id
     *
     * @return array
     */
    public function getDiscordIDs(): array
    {
        $discords = [];

        $ids = $this->getIdentifiers();

        foreach ($ids as $id) {
            if (Str::startsWith($id, 'discord:')) {
                $discord = str_replace('discord:', '', $id);

                if (!in_array($discord, $discords)) {
                    $discords[] = $discord;
                }
            }
        }

        return $discords;
    }

    /**
     * Gets a URL to the player's steam profile.
     *
     * @return string
     */
    public function getSteamProfileUrl(): ?string
    {
        $steamId = $this->getSteamID();

        return $steamId ? self::STEAM_INVITE_URL . $steamId->RenderSteamInvite() : null;
    }

    /**
     * Gets all user variables.
     *
     * @return array
     */
    public function getUserVariables(): array
    {
        $variables = $this->user_variables ?? [];

        return $variables;
    }

    /**
     * Gets all the steam identifiers.
     *
     * @return array
     */
    public function getSteamIdentifiers(): array
    {
        $lastUsed = array_values(
            array_filter(
                $this->getLastUsedIdentifiers(),
                function ($identifier) {
                    return Str::startsWith($identifier, 'steam:');
                }
            )
        );

        if (!empty($lastUsed)) {
            return $lastUsed;
        }

        return array_values(
            array_filter(
                $this->getIdentifiers(),
                function ($identifier) {
                    return Str::startsWith($identifier, 'steam:');
                }
            )
        );
    }

    /**
     * Gets all the identifiers.
     *
     * @return array
     */
    public function getIdentifiers(): array
    {
        $identifiers   = $this->identifiers ?? [];
        $identifiers[] = $this->license_identifier;

        return array_values(
            array_unique(
                $identifiers
            )
        );
    }

    /**
     * Gets the last used identifiers.
     *
     * @return array
     */
    public function getLastUsedIdentifiers(bool $ignoreLicense2 = false): array
    {
        $identifiers = $this->last_used_identifiers ?? [];

        if (is_string($identifiers)) {
            $identifiers = json_decode($identifiers, true) ?? [];
        }

        return array_values(
            array_filter(
                array_unique(
                    $identifiers
                ),
                function ($identifier) use ($ignoreLicense2) {
                    return $ignoreLicense2 ? !Str::startsWith($identifier, 'license2:') : true;
                }
            )
        );
    }

    /**
     * Gets all the identifiers.
     *
     * @return array
     */
    public static function isLinked(array $identifiers1, array $identifiers2): bool
    {
        $identifiers1 = array_values(array_filter($identifiers1, function ($identifier) {
            return !Str::startsWith($identifier, 'ip:');
        }));

        $identifiers2 = array_values(array_filter($identifiers2, function ($identifier) {
            return !Str::startsWith($identifier, 'ip:');
        }));

        return !empty(array_intersect($identifiers1, $identifiers2));
    }

    /**
     * Gets all the ips.
     *
     * @return array
     */
    public function getIps(): array
    {
        $ips = $this->ips ?? [];

        return array_values(
            array_unique(
                $ips
            )
        );
    }

    /**
     * Gets all the tokens.
     *
     * @return array
     */
    public function getTokens(): array
    {
        $tokens = $this->player_tokens ?? [];

        return array_values(
            array_unique(
                $tokens
            )
        );
    }

    /**
     * Returns all bannable identifiers
     *
     * @return array
     */
    public function getBannableIdentifiers(): array
    {
        return array_values(array_filter($this->getIdentifiers(), function ($identifier) {
            return !Str::startsWith($identifier, 'ip:');
        }));
    }

    /**
     * Gets the identifier for the provided key.
     *
     * @param $key
     * @return mixed|null
     */
    public function getIdentifier($key)
    {
        foreach ($this->getIdentifiers() as $identifier) {
            if (strpos($identifier, $key) === 0) {
                return $identifier;
            }

        }
        return null;
    }

    /**
     * Gets the opfw fingerprint.
     *
     * @param $key
     * @return mixed|null
     */
    public function getFingerprint()
    {
        $variables = $this->getUserVariables();

        return isset($variables['ofFingerprint']) ? $variables['ofFingerprint'] : null;
    }

    /**
     * Checks whether this player is a staff member.
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        return ($this->is_staff ?? false) || $this->isSeniorStaff();
    }

    /**
     * Checks whether this player is a senior staff member.
     *
     * @return bool
     */
    public function isSeniorStaff(): bool
    {
        return ($this->is_senior_staff ?? false) || $this->isSuperAdmin();
    }

    /**
     * Checks whether this player is a super admin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return ($this->is_super_admin ?? false) || $this->isRoot();
    }

    /**
     * Checks whether this player has root access to the panel.
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->license_identifier && GeneralHelper::isUserRoot($this->license_identifier);
    }

    /**
     * Checks whether this player is a debugger.
     *
     * @return bool
     */
    public function isDebugger(): bool
    {
        return $this->isSuperAdmin() || $this->is_debugger;
    }

    /**
     * Checks whether player is banned.
     *
     * @return bool
     */
    public function isBanned(): bool
    {
        return !is_null($this->getActiveBan());
    }

    /**
     * Gets the active ban.
     *
     * @return Ban
     */
    public function getActiveBan(): ?Ban
    {
        if ($this->ban === false) {
            $this->ban = $this->bans()
                ->get()
                ->first();
        }

        return $this->ban;
    }

    /**
     * Gets the steam id.
     *
     * @return SteamID|null
     */
    public function getSteamID(): ?SteamID
    {
        return get_steam_id($this->getSteamIdentifier());
    }

    /**
     * Gets the characters' relationship.
     *
     * @return HasMany
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'license_identifier', 'license_identifier')->orderBy('character_slot');
    }

    /**
     * Gets the logs' relationship.
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'identifier', 'license_identifier');
    }

    /**
     * Gets the warnings' relationship.
     *
     * @return HasMany
     */
    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class, 'player_id', 'user_id');
    }

    public function fasterWarnings(bool $includeHidden = false): array
    {
        $warnings = Warning::query()
            ->select(['id', 'message', 'warning_type', 'created_at', 'updated_at', 'player_name', 'license_identifier', 'can_be_deleted'])
            ->where('player_id', '=', $this->user_id)
            ->leftJoin('users', 'issuer_id', '=', 'user_id');

        if (!$includeHidden) {
            $warnings = $warnings->where('warning_type', '!=', Warning::TypeHidden);
        }

        $warnings = $warnings->get();

        $plainWarnings = [];
        foreach ($warnings as $warning) {
            $plainWarnings[] = [
                'id'          => $warning->id,
                'message'     => $warning->message,
                'warningType' => $warning->warning_type,
                'createdAt'   => $warning->created_at,
                'updatedAt'   => $warning->updated_at,
                'canDelete'   => $warning->can_be_deleted,
                'issuer'      => [
                    'playerName'        => $warning->player_name,
                    'licenseIdentifier' => $warning->license_identifier,
                ],
            ];
        }

        return $plainWarnings;
    }

    public function getHWIDBanHash(): ?array
    {
        $ban = DB::table('user_bans')
            ->select(['ban_hash', 'license_identifier'])
            ->leftJoin('users', 'identifier', '=', 'license_identifier')
            ->where(DB::raw("JSON_OVERLAPS(player_tokens, '" . json_encode($this->getTokens()) . "')"), '=', '1')
            ->whereNotNull('ban_hash')
            ->first();

        return $ban ? [
            'hash'    => $ban->ban_hash,
            'license' => $ban->license_identifier,
        ] : null;
    }

    /**
     * Gets the panel_logs relationship.
     *
     * @return HasMany
     */
    public function panelLogs(): HasMany
    {
        return $this->hasMany(PanelLog::class, 'target_identifier', 'license_identifier');
    }

    /**
     * Gets the query for bans.
     *
     * @return Builder
     */
    public function bans(): Builder
    {
        return Ban::query()->whereIn('identifier', $this->getIdentifiers());
    }

    /**
     * Gets the unique ban hashes associated with this player.
     *
     * @return array
     */
    public function uniqueBans(): array
    {
        $bans = $this->bans()->select(['ban_hash'])->groupBy('ban_hash')->get();

        return array_map(function ($ban) {
            return $ban['ban_hash'];
        }, $bans->toArray());
    }

    /**
     * Returns a map of licenseIdentifier->serverId,server for each online player
     *
     * @param bool $useCache
     * @return array|null
     */
    public static function getAllOnlinePlayers(bool $useCache): ?array
    {
        $serverIps = explode(',', env('OP_FW_SERVERS', ''));

        if (!$serverIps) {
            return [];
        }

        $result = [];
        foreach ($serverIps as $serverIp) {
            if ($serverIp) {
                $licenseIdentifiers = Server::fetchLicenseIdentifiers($serverIp, $useCache);

                if ($licenseIdentifiers === null) {
                    return null;
                }

                foreach ($licenseIdentifiers as $key => $player) {
                    if (!isset($result[$key])) {
                        // User flags
                        $flags = $player['flags'];

                        $fake     = !!($flags & 2);
                        $minigame = !!($flags & 4);
                        $camCords = !!($flags & 8);
                        $queue    = !!($flags & 16);

                        // Character flags
                        $characterFlags = $player['characterFlags'] ?? 0;

                        $characterData = [];

                        !!($characterFlags & 1) && $characterData[]   = 'dead';
                        !!($characterFlags & 2) && $characterData[]   = 'trunk';
                        !!($characterFlags & 4) && $characterData[]   = 'in_shell';
                        !!($characterFlags & 8) && $characterData[]   = 'invisible';
                        !!($characterFlags & 16) && $characterData[]  = 'invincible';
                        !!($characterFlags & 32) && $characterData[]  = 'frozen';
                        !!($characterFlags & 64) && $characterData[]  = 'spawned';
                        !!($characterFlags & 128) && $characterData[] = 'no_collisions';
                        !!($characterFlags & 256) && $characterData[] = 'no_gameplay_cam';

                        $result[$key] = [
                            'id'               => intval($player['source']),
                            'character'        => $player['character'],
                            'license'          => $key,
                            'server'           => $serverIp,
                            'fakeDisconnected' => $fake,
                            'fakeName'         => !!($flags & 1) ? $player['name'] : null,
                            'minigame'         => $minigame,
                            'camCords'         => $camCords,
                            'queue'            => $queue,
                            'characterData'    => $characterData,
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns the online status of the player
     *
     * @param string $licenseIdentifier
     * @param bool $useCache
     * @return PlayerStatus
     */
    public static function getOnlineStatus(string $licenseIdentifier, bool $useCache, bool $trueStatus = false): PlayerStatus
    {
        $serverIps = explode(',', env('OP_FW_SERVERS', ''));

        if (!$serverIps) {
            return new PlayerStatus(PlayerStatus::STATUS_UNAVAILABLE, '', 0);
        }

        $players = self::getAllOnlinePlayers($useCache);

        if ($players === null) {
            return new PlayerStatus(PlayerStatus::STATUS_UNAVAILABLE, '', 0);
        }

        if (isset($players[$licenseIdentifier])) {
            $player = $players[$licenseIdentifier];

            if (!$trueStatus && ($player['fakeDisconnected'] || $player['fakeName'])) {
                return new PlayerStatus(PlayerStatus::STATUS_OFFLINE, '', 0);
            }

            return new PlayerStatus(PlayerStatus::STATUS_ONLINE, $player['server'], $player['id'], $player['character'], $player['fakeName'], [
                'minigame' => $player['minigame'],
                'camCords' => $player['camCords'],
                'queue'    => $player['queue'],
            ], $player['characterData']);
        }

        return new PlayerStatus(PlayerStatus::STATUS_OFFLINE, '', 0);
    }

    /**
     * Returns a map of licenseIdentifier->player_name
     * This is used instead of a left join as it appears to be a lot faster
     *
     * @param array $source
     * @param string|array $sourceKey
     * @return array
     */
    public static function fetchLicensePlayerNameMap(array $source, $sourceKey): array
    {
        if (!is_array($sourceKey)) {
            $sourceKey = [$sourceKey];
        }

        $identifiers = [];
        foreach ($source as $entry) {
            foreach ($sourceKey as $key) {
                $d = is_array($entry) ? $entry[$key] : $entry->$key;

                if ($d && !in_array($d, $identifiers)) {
                    $identifiers[] = $d;
                }
            }
        }

        $identifiers = array_values(array_unique($identifiers));
        $playerMap   = CacheHelper::loadLicensePlayerNameMap($identifiers);

        if (empty($playerMap)) {
            $playerMap['empty'] = 'empty';
        }

        return $playerMap;
    }

    public static function getIdentifierLabel(string $identifier): ?string
    {
        $type = explode(':', $identifier)[0];

        switch ($type) {
            case 'ip':
                return 'IP-Address';
            case 'steam':
                return 'Steam Account';
            case 'discord':
                return 'Discord Account';
            case 'fivem':
                return 'FiveM Account';
            case 'license':
            case 'license2':
                return 'Rockstar Account';
            case 'live':
                return 'Microsoft Account';
            case 'xbl':
                return 'XBox Live';
            default:
                return null;
        }
    }

    public static function isValidIdentifier(string $identifier): bool
    {
        return sizeof(explode(':', $identifier)) === 2 && self::getIdentifierLabel($identifier) !== null;
    }
}

/**
 * Takes the given identifier and tries to resolve a SteamID from it.
 *
 * @param string $identifier
 * @return SteamID|null
 */
function get_steam_id(?string $identifier): ?SteamID
{
    if (!$identifier) {
        return null;
    }

    try {
        // Get rid of any prefix.
        return new SteamID(hexdec(explode('steam:', $identifier)[1]));
    } catch (Exception $ex) {
        return null;
    }
}
