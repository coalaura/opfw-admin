<?php
namespace App;

use App\Helpers\CacheHelper;
use App\Helpers\DeviceHelper;
use App\Helpers\GeneralHelper;
use App\Helpers\RootHelper;
use App\Helpers\ServerAPI;
use App\Helpers\StatusHelper;
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
        'player_tokens',
        'is_bot',
        'is_staff',
        'is_senior_staff',
        'is_super_admin',
        'is_trusted',
        'is_debugger',
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
        'admin_features',
        'user_settings',
        'user_data',
        'user_statistics',
        'refresh_tokens',
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
        'player_aliases'        => 'array',
        'enabled_commands'      => 'array',
        'admin_features'        => 'array',
        'user_settings'         => 'array',
        'user_data'             => 'array',
        'user_statistics'       => 'array',
        'staff_points'          => 'array',
        'last_connection'       => 'datetime',
        'is_bot'                => 'boolean',
        'is_trusted'            => 'boolean',
        'is_staff'              => 'boolean',
        'is_super_admin'        => 'boolean',
        'is_debugger'           => 'boolean',
        'playtime'              => 'integer',
        'total_joins'           => 'integer',
        'priority_level'        => 'integer',
        'panel_settings'        => 'array',
        'weekly_playtime'       => 'array',
        'media_devices'         => 'array',
        'media_device_ids'      => 'array',
    ];

    /**
     * Cached active ban.
     *
     * @var ?Ban
     */
    private $ban = null;

    /**
     * Cached user variables.
     *
     * @var ?array
     */
    private $userVariables = null;

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
        "relativeTime"    => [
            "type"    => "boolean",
            "default" => false,
        ],
        "locale"          => [
            "type"    => "string",
            "default" => "en-us",
            "options" => [
                "en-us"   => "English",
                "no"      => "Norwegian",
                "hi-lat"  => "Hindi",

                "en-cave" => "Caveman Speak",
                "en-uwu"  => "UwU Language",
                "en-us_s" => "Southern Accent",
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

    public function getAuthIdentifierName()
    {
        return 'user_id';
    }

    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    public function setPanelSetting(string $key, $value,  ? callable $progress = null)
    {
        $info = self::PlayerSettings[$key] ?? null;

        if (! $info) {
            throw new Exception('Invalid settings key: ' . $key);
        }

        $settings = $this->panel_settings ?? [];

        $expected = $info['type'];

        if ($expected === 'url') {
            if (! $value) {
                $value = '';
            }

            $previous = $settings[$key] ?? null;

            $presets = $info['presets'] ?? [];

            $isPreset  = in_array($value, $presets);
            $wasPreset = in_array($previous, $presets);

            // We only need to validate the URL if it's not a preset
            if ($value !== '' && ! $isPreset) {
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new Exception('Input is not a valid URL.');
                }

                if (! preg_match('/^https:\/\/[^\s?]+?\.(png|jpe?g|webp)(\?[^\s]*)?$/mi', $value)) {
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

                if (! $data) {
                    // Imgur is blocking pretty hard for some reason... :(
                    if (Str::startsWith($url, 'https://i.imgur.com/')) {
                        throw new Exception('Imgur is blocking the download of this image.');
                    }

                    throw new Exception('Failed to download image.');
                }

                $dir = public_path('/_uploads/');
                if (! file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

                $progress && $progress("resize");

                GeneralHelper::renderThumbnail(public_path($value), $data, 1280, 720, true);
            }

            if ($previous && ! $wasPreset && preg_match('/^\/_uploads\/[a-f0-9]+\.(png|jpe?g|webp)$/mi', $previous)) {
                $file = public_path($previous);

                if (file_exists($file)) {
                    unlink($file);
                }
            }
        } else if (gettype($value) !== $expected) {
            throw new Exception('Invalid input type, expected ' . $expected . '.');
        }

        if (isset($info['options']) && ! isset($info['options'][$value])) {
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

        if (! $setting) {
            return null;
        }

        $value = $settings[$key] ?? $setting['default'];
        if ($value && preg_match('/^\/_uploads\/[a-f0-9]+\.(png|jpe?g|webp)$/mi', $value)) {
            $file = public_path($value);

            if (! file_exists($file)) {
                $value = '';
            }
        }

        return $value;
    }

    public function getPanelSettings() : array
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

    public function consoleName(): string
    {
        return sprintf("%s (%s)", $this->getSafePlayerName(), $this->license_identifier);
    }

    public function discordId(): ?string
    {
        $lastUsed = $this->getLastUsedIdentifiers();

        foreach ($lastUsed as $identifier) {
            if (Str::startsWith($identifier, 'discord:')) {
                return str_replace('discord:', '', $identifier);
            }
        }

        return null;
    }

    public function hasEnabledCommands(string ...$enabled): bool
    {
        $commands = $this->enabled_commands ?? [];

        if (empty($commands)) {
            return false;
        }

        foreach($enabled as $cmd) {
            if (!in_array($cmd, $commands)) {
                return false;
            }
        }

        return true;
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

    public function isSpectatorModeEnabled(): bool
    {
        $data = $this->user_data ?? [];

        return isset($data['spectatorMode']) && $data['spectatorMode'];
    }

    public function isSpectatorCameraEnabled(): bool
    {
        $data = $this->user_data ?? [];

        return isset($data['spectatorCamera']) && $data['spectatorCamera'];
    }

    public function isIdleCamDisabled(): bool
    {
        $settings = $this->user_settings ?? [];

        return isset($settings['idleCamDisabled']) && $settings['idleCamDisabled'];
    }

    public function isAdvancedMetagameEnabled(): bool
    {
        $features = $this->admin_features ?? [];

        return isset($features['advanced_metagame']) && ! empty($features['advanced_metagame']) && $features['advanced_metagame']['state'];
    }

    private static function filterPlayerName(string $name): string
    {
        foreach (self::PlayerNameFilter as $filter) {
            $name = str_ireplace($filter, '', $name);
        }

        $name = preg_replace('/[\p{Cc}\p{Cf}]/u', '', $name);

        return trim($name);
    }

    public static function getFilteredPlayerName(string $name, $aliases, ?string $license): string
    {
        $name    = self::filterPlayerName($name);
        $aliases = $aliases ?? [];

        if (is_string($aliases)) {
            $aliases = json_decode($aliases, true) ?? [];
        }

        if ($name) {
            return $name;
        }

        foreach ($aliases as $alias) {
            if (! preg_match('/[a-z0-9]/i', $alias)) {
                continue;
            }

            $name = self::filterPlayerName($alias);

            if ($name) {
                return $name;
            }
        }

        return $license ? substr($license, 8, 10) : "unknown";
    }

    public function getSafePlayerName(): string
    {
        return self::getFilteredPlayerName($this->player_name ?? "", $this->player_aliases, $this->license_identifier);
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
        if ($refreshCache || ! CacheHelper::exists('tags')) {
            $tags = self::query()->select(['panel_tag'])->whereNotNull('panel_tag')->groupBy('panel_tag')->get()->toArray();

            CacheHelper::write('tags', $tags, CacheHelper::WEEK);
        }

        return CacheHelper::read('tags', []);
    }

    public function getActiveMute(): ?array
    {
        $data = $this->user_data ?? [];

        if (! isset($data['muted']) || ! $data['muted']) {
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

        if ($value && ! empty($value)) {
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
        if (preg_match('/^steam:[a-z0-9]+$/mi', $value)) {
            $id = '"' . $value . '"';

            return Player::query()->where(DB::raw("JSON_CONTAINS(identifiers, '$id')"), '=', '1')->get();
        }

        // License Identifier
        if (preg_match('/^license:[a-z0-9]+$/mi', $value)) {
            return Player::query()->select()->where('license_identifier', '=', $value)->first();
        }

        // User ID hex
        if (Str::startsWith($value, '0x')) {
            $id = hexdec($value);
        } else {
            $id = intval($value);
        }

        if (! $id) {
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

                if (! in_array($discord, $discords)) {
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
     * @return ?array
     */
    public function getUserVariables(): ?array
    {
        if (! $this->userVariables) {
            $this->userVariables = (array) DB::table("user_variables")
                ->where("user_id", "=", $this->user_id)
                ->first();
        }

        return $this->userVariables;
    }

    /**
     * Gets the users timezone.
     *
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        $variables = $this->getUserVariables();

        return $variables['tz_name'] ?? null;
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

        if (! empty($lastUsed)) {
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
                    return $ignoreLicense2 ? ! Str::startsWith($identifier, 'license2:') : true;
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
            return ! Str::startsWith($identifier, 'ip:');
        }));

        $identifiers2 = array_values(array_filter($identifiers2, function ($identifier) {
            return ! Str::startsWith($identifier, 'ip:');
        }));

        return ! empty(array_intersect($identifiers1, $identifiers2));
    }

    /**
     * Gets all the ips.
     *
     * @return array
     */
    public function getIps(): array
    {
        $identifiers = $this->identifiers ?? [];

        return array_values(
            array_unique(
                array_filter(
                    array_map(function ($identifier) {
                        if (Str::startsWith($identifier, "ip:")) {
                            return substr($identifier, 3);
                        }

                        return false;
                    }, $identifiers)
                )
            )
        );
    }

    /**
     * Gets the last used ips.
     *
     * @return array
     */
    public function getLastIps(): array
    {
        $identifiers = $this->last_used_identifiers ?? [];

        return array_values(
            array_unique(
                array_filter(
                    array_map(function ($identifier) {
                        if (Str::startsWith($identifier, "ip:")) {
                            return substr($identifier, 3);
                        }

                        return false;
                    }, $identifiers)
                )
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
            return ! Str::startsWith($identifier, 'ip:');
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
     * Gets the media devices.
     *
     * @return array
     */
    public function getComparableMediaDevices(): array
    {
        $devices = $this->media_devices ?? [];

        return array_values(array_filter($devices, function ($device) {
            if (strpos($device, 'obs_virtual') !== false) {
                return false;
            } else if (strpos($device, 'nvidia_broadcast') !== false) {
                return false;
            } else if (strpos($device, 'voicemeeter') !== false) {
                return false;
            } else if (strpos($device, 'gpu_') === 0) {
                return false;
            }

            // Ingore empty devices
            return ! in_array($device, [
                'videoinput_',
                'audioinput_',
                'audiooutput_',
                'videooutput_',
            ]);
        }));
    }

    /**
     * Gets the media device ids.
     *
     * @return array
     */
    public function getComparableMediaDeviceIds(): array
    {
        return $this->media_device_ids ?? [];
    }

    /**
     * Gets the gpu media device.
     *
     * @return string|null
     */
    public function getGPUMediaDevice(): ?string
    {
        $devices = $this->media_devices ?? [];

        foreach ($devices as $device) {
            if (strpos($device, 'gpu_') === 0) {
                return $device;
            }
        }

        return null;
    }

    /**
     * Check if the media devices all have ranomized names, usually signs of a spoofer.
     */
    public function areMediaDevicesSuspicious(): bool
    {
        $devices = $this->media_devices;

        if ($devices === null) {
            return false; // not yet collected
        }

        return DeviceHelper::check($devices);
    }

    /**
     * Gets the matching user variables. Like screen resolution, timezone, etc.
     */
    public function getMatchingVariables(array $variables): array
    {
        $source = $this->getUserVariables();

        $matches = [];

        // Screen resolution
        $sResolution = sprintf('%dx%d', $source['screen_width'] ?? 0, $source['screen_height'] ?? 0);
        $tResolution = sprintf('%dx%d', $variables['screen_width'] ?? 0, $variables['screen_height'] ?? 0);

        if ($sResolution === $tResolution && $sResolution !== '0x0') {
            $matches[] = sprintf('Resolution: %s == %s', $sResolution, $tResolution);
        }

        // Timezone
        $sTimezone = $source['tz_name'] ?? null;
        $tTimezone = $variables['tz_name'] ?? null;

        if ($sTimezone === $tTimezone && $sTimezone !== null) {
            $matches[] = sprintf('Timezone: %s == %s', $sTimezone, $tTimezone);
        }

        // CPU Thread Count
        $sThreads = $source['cpu_threads'] ?? 0;
        $tThreads = $variables['cpu_threads'] ?? 0;

        if ($sThreads === $tThreads && $sThreads > 0) {
            $matches[] = sprintf('Threads: %s == %s', $sThreads, $tThreads);
        }

        return $matches;
    }

    /**
     * Checks whether this player is a bot.
     *
     * @return bool
     */
    public function isBot(): bool
    {
        return $this->is_bot ?? false;
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
        return RootHelper::isUserRoot($this->license_identifier);
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
            ->select(['id', 'message', 'warning_type', 'created_at', 'updated_at', 'player_name', 'license_identifier', 'can_be_deleted', 'reactions'])
            ->where('player_id', '=', $this->user_id)
            ->leftJoin('users', 'issuer_id', '=', 'user_id');

        if (! $includeHidden) {
            $warnings = $warnings->where('warning_type', '!=', Warning::TypeHidden);
        }

        $warnings = $warnings->get();

        $loggedInLicense = license();

        $plainWarnings = [];
        foreach ($warnings as $warning) {
            $plainWarnings[] = [
                'id'          => $warning->id,
                'message'     => $warning->message,
                'warningType' => $warning->warning_type,
                'createdAt'   => $warning->created_at,
                'updatedAt'   => $warning->updated_at,
                'canDelete'   => $warning->can_be_deleted,
                'reactions'   => $warning->getReactions($loggedInLicense),
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
     * Gets the query for bans.
     *
     * @return Builder
     */
    public function bans(): Builder
    {
        return Ban::query()->whereIn('identifier', $this->getIdentifiers());
    }

    /**
     * Checks whether player is banned.
     *
     * @return bool
     */
    public function isBanned(): bool
    {
        return ! ! $this->bans()->get()->first();
    }

    /**
     * Gets the unique bans associated with this player.
     */
    public function uniqueBans()
    {
        return $this->bans()
            ->orderBy('timestamp', 'desc')
            ->groupBy('ban_hash')
            ->get();
    }

    /**
     * Track an action in the user statistics.
     */
    public function trackStatistics(string $action)
    {
        DB::table('staff_statistics')->insert([
            'identifier' => $this->license_identifier,
            'action'     => $action,
            'timestamp'  => time(),
        ]);
    }

    public static function getEnablableCommands(): array
    {
        $commands = ServerAPI::getPermissions();

        return array_keys($commands);
    }

    /**
     * Calculates the users total XP.
     */
    public static function calculateXP(array $actions): float
    {
        $get = function ($action) use ($actions): int {
            return $actions[$action] ?? 0;
        };

        $xp = 0.0;

        // claimed-report is worth x1.8
        $xp += $get('claimed-report') * 1.8;

        // sent-staff-pm is worth x0.26
        $xp += $get('sent-staff-pm') * 0.26;

        // created-report is worth -x1.2
        $xp -= $get('created-report') * 1.2;

        // kicked-player is worth x1.2
        $xp += $get('kicked-player') * 1.2;

        // banned-player is worth x1.8
        $xp += $get('banned-player') * 1.8;

        // set-player-job is worth x0.58
        $xp += $get('set-player-job') * 0.58;

        // muted-player is worth x1.25
        $xp += $get('muted-player') * 1.25;

        // revived-player is worth x0.18
        $xp += $get('revived-player') * 0.18;

        // spectated-player is worth x0.11
        $xp += $get('spectated-player') * 0.11;

        // unloaded-player is worth x0.8
        $xp += $get('unloaded-player') * 0.8;

        // wiped-entities is worth x0.5
        $xp += $get('wiped-entities') * 0.5;

        // froze-player is worth x0.4
        $xp += $get('froze-player') * 0.4;

        // slapped-player is worth x0.18
        $xp += $get('slapped-player') * 0.18;

        // damaged-player is worth x0.04
        $xp += $get('damaged-player') * 0.04;

        // reskinned-player is worth x0.3
        $xp += $get('reskinned-player') * 0.3;

        // set-player-armor is worth x0.12
        $xp += $get('set-player-armor') * 0.12;

        return $xp;
    }

    /**
     * Returns the online status of the player
     *
     * @param string $licenseIdentifier
     * @param bool $trueStatus
     * @return PlayerStatus
     */
    public static function getOnlineStatus(string $licenseIdentifier, bool $trueStatus = false): PlayerStatus
    {
        $player = StatusHelper::get($licenseIdentifier);

        if (! $player) {
            return new PlayerStatus(PlayerStatus::STATUS_OFFLINE, '', 0);
        }

        if (! $trueStatus && $player['fakeDisconnected']) {
            return new PlayerStatus(PlayerStatus::STATUS_OFFLINE, '', 0);
        }

        return new PlayerStatus(PlayerStatus::STATUS_ONLINE, $player['server'], $player['source'], $player['character'] ? $player['character']['id'] : null, $player['characterData']);
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
        if ($sourceKey) {
            if (! is_array($sourceKey)) {
                $sourceKey = [$sourceKey];
            }

            $identifiers = [];

            foreach ($source as $entry) {
                foreach ($sourceKey as $key) {
                    $d = is_array($entry) ? $entry[$key] : $entry->$key;

                    if ($d && ! in_array($d, $identifiers)) {
                        $identifiers[] = $d;
                    }
                }
            }
        } else {
            $identifiers = $source;
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

    public static function getNewPlayers()
    {
        $playerList = StatusHelper::all();
        $players    = array_keys($playerList);

        return self::query()
            ->whereIn('license_identifier', $players)
            ->where('playtime', '<=', 60 * 60 * 36)
            ->orderBy('playtime')
            ->get();
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
    if (! $identifier) {
        return null;
    }

    try {
        // Get rid of any prefix.
        return new SteamID(hexdec(explode('steam:', $identifier)[1]));
    } catch (Exception $ex) {
        return null;
    }
}
