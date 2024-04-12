<?php

namespace App;

use App\Helpers\CacheHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A ban that can be issued by a player and received by a players.
 *
 * @package App
 */
class Ban extends Model
{
    use HasFactory;

    /**
     * Column name for when the model was created.
     */
    const CREATED_AT = 'timestamp';

    /**
     * Column name for when the model was last updated.
     */
    const UPDATED_AT = 'timestamp';

    /**
     * @var array
     */
    protected static $bans = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_bans';

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ban_hash',
        'identifier',
        'smurf_account',
        'creator_name',
        'creator_identifier',
        'reason',
        'timestamp',
        'expire',
        'locked',
        'scheduled_unban',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'locked'    => 'boolean',
        'timestamp' => 'datetime',
    ];

    private static $automatedReasons = null;

    const SYSTEM_INFO = [
        "MODDING" => [
            "SPECTATING"               => "Impossible to be scuff. To spectate someone you have to toggle spectator mode on their ped. That can only be done through script, as you have to run a native to do so. We don't use spectator mode anywhere in the framework. If you have spectator mode on, you are modding.",
            "INVINCIBILITY"            => "Impossible to be scuff. Invincibility can only be toggled through script. We keep track of every time the framework toggles your invincibility on/off. If you are found invincible outside of those times, you had to have injected some script or used a menu to do so.",
            "RUNTIME_TEXTURE"          => "Impossible to be scuff. Runtime textures are textures dynamically created and loaded through script. A few mod menus use runtime textures to display things like their logo. We have a list of known runtime textures used by mod menus. If one of those textures is loaded, that means you had to have used some script that loaded it.",
            "TEXT_ENTRY"               => "Impossible to be scuff. Text entries are used to display text on the screen. They can only be created through script. We have a list of known text entries used by mod menus. If one of those text entries is created, that means you had to have used some script that created it.",
            "PLAYER_BLIPS"             => "Impossible to be scuff. The majority of lua mod menus all use the same method to create player blips. All of the frameworks blips like PD/EMS trackers, etc. are created through a different method. If you have blips for players locations on your map, you had to have used some script to create them.",
            "ILLEGAL_FREEZE"           => "Impossible to be scuff. Freezing a player can only be done through script. We keep track of every time the framework freezes a player. If you are found frozen outside of those times, you had to have injected some script or used a menu to do so. Freezing the player is common during noclip for example, to prevent you from constantly falling.",
            "INVALID_HEALTH"           => "Impossible to be scuff. By default all player peds have 200 health and a max health of 200. Those values can only be changed through script. If your health or max health is found to be different than 200, you had to have injected some script or used a menu to do so.",
            "CLEAR_TASKS"              => "Impossible to be scuff. Clearing a peds tasks is done to have it stop doing what its doing basically. This can only be done through script. This detection watches if you are trying to clear another players tasks. Mod menus sometimes do that to kick other players out of their vehicles for example. If you are found trying to clear another players tasks, you had to have injected some script or used a menu to do so.",
            "ILLEGAL_NATIVE"           => "Impossible to be scuff. A native is a function that is built into the game. You can only call natives through script. We keep track of a list of natives like creating a vehicle, trying to force ownership of an entity, etc. We don't use any of those natives in the framework, so if you are found calling one of them, you had to have injected some script or used a menu to do so.",
            "HONEYPOT"                 => "Impossible to be scuff. Honeypots are \"traps\" that are set in places modders might be looking in. They are called honeypots since they have very lucrative names like \"stealCash\" or \"giveWeapon\". If you are found calling one of those honeypots, you had to have injected some script or used a menu to do so.",
            "ILLEGAL_GLOBAL"           => "Impossible to be scuff. A global is a variable that is accessible from anywhere within script. We keep track of a list of globals like \"ESX\" or \"QBCore\". We don't use any of those globals in the framework, so if you are found accessing one of them, you had to have injected some script or used a menu to do so.",
            "DAMAGE_MODIFIER"          => "Impossible to be scuff. The framework uses damage modifiers to adjust the damage of weapons. Every single usable weapon in the framework has a modifier associated to it. If you are found using a different modifier (for example a higher one), you had to have injected some script or used a menu to do so, since they can only be changed through script.",
            "FREECAM"                  => "Impossible to be scuff. By default your game renders the so called gameplay camera. Through script you can create different cameras to for example freecam around. We keep track of every time the framework creates a camera. If you are found with a camera created outside of those times, you had to have injected some script or used a menu to do so. Very rarely the game creates a different camera natively, but we have exceptions for those. This detection has been literally 100% accurate so far and even caught a closet cheater.",

            "ILLEGAL_VEHICLE_MODIFIER" => "Highly unlikely to be scuff. Vehicle modifiers are used to for example increase your vehicles gravity, top speed, acceleration, etc. We keep track of every time the framework uses any of those modifiers. If you are found using one of those modifiers outside of those times, you had to have injected some script or used a menu to do so. Very rarely those modifiers differ natively but we reset them every time you switch vehicles.",
            "WEAPON_SPAWN"             => "Highly unlikely to be scuff. The framework gives and removes weapons from your ped depending on what weapon you are trying to or have currently equipped. If you are trying to give yourself a weapon different to the one you currently have equipped, you had to have done so through script or a menu. When an armed ped dies it sometimes drops its weapon and when you pick it up you are theoretically giving yourself a weapon. However we have exceptions for those cases and usually remove all dropped weapons almost instantly.",
            "PED_CHANGE"               => "Highly unlikely to be scuff. You can only change your ped through script. We keep track of every time the framework changes or modifies your ped. For example when spawning in, changing and taking off clothes. If you are found changing your ped outside of those times, you had to have injected some script or used a menu to do so. Your ped can change natively in some rare occasions but we have exceptions for all of those cases.",
            "THERMAL_NIGHTVISION"      => "Highly unlikely to be scuff. Thermal and night vision can only be toggled through script. We keep track of every time the framework toggles your thermal/night vision on/off. If you are found with thermal/night vision on outside of those times, you had to have injected some script or used a menu to do so. When using a night-vision / thermal scope you are also toggling your thermal/night vision on/off, but we have exceptions for those cases.",
            "HOTWIRE_DRIVING"          => "Highly unlikely to be scuff. When you haven't hotwired a car and you don't have keys to it, the framework forcefully turns the engine off every single frame. This detection checks if your engine has been running continuously for a certain period of time, while having to hotwire a car. Some mod menus have the ability to override the state of a vehicles engine, basically bypassing the hotwire system. If you are found driving a car without hotwiring it, you had to have injected some script or used a menu to do so.",
            "SUSPICIOUS_EXPLOSION"     => "Highly unlikely to be scuff. There is a bunch of different explosion types modders use to blow people up. Those explosions do occur natively but they have specific metadata attached to them making it somewhat easy to differentiate between native explosions and ones that were manually created through script or a menu.",
            "UNDERGROUND"              => "Highly unlikely to be scuff. When teleporting, some mod menus will place your ped very far below the ground and then move it to the target to avoid being detected by anti-cheats. This detection tracks the distance you've traveled while being far below the ground. If you are found to have traveled a certain distance while being far below the ground, you probably used a menu to do so.",
            "ILLEGAL_DAMAGE"           => "Highly unlikely to be scuff. Every time you deal damage to another player you do so through the server (You -> Server -> Other player). When you do so, we check a variety of things like: Do you have the weapon you are trying to damage with? Is the damage much higher than expected for that weapon? Etc. If any of those checks fail, you are probably modding. The metadata of this detection will contain a lot more information.",
            "VEHICLE_MODIFICATION"     => "Highly unlikely to be scuff. Things like a vehicles engine level, breaks, transmission, turbo, etc. can only be changed through script. We keep track of every time the framework changes any of those values. If you are found changing any of those values outside of those times, you had to have injected some script or used a menu to do so.",
            "ADVANCED_NOCLIP"          => "Highly unlikely to be scuff. Certain noclip scripts are able to sneak past our other detections by not freezing the player ped or using a script camera. However most of those scripts will have the player move in the direction the camera is facing. This detection checks if the W key is pressed and the player just moved in the exact same direction as the camera is facing. If this happens continuously for a certain period of time and a few other checks are met, you are most likely using a noclip script.",
            "ILLEGAL_LOCAL_VEHICLE"    => "Highly unlikely to be scuff. Every entity in the game can be either networked or not networked. If it is networked it is visible to all players. If it is not-networked, the entity only exist on your client. Vehicles that are driven around should never be not-networked, since all ambient vehicles (ones that are spawned natively by the game) and all vehicles spawned in by the framework are networked. If you are found driving a not-networked vehicle, you had to have injected some script or used a menu to spawn said vehicle in, since there is no other way to get a drivable not-networked vehicle.",

            "BAD_ENTITY_SPAWN"         => "Very unlikely to be scuff. Every object in the game has a bunch of metadata attached to it. Objects spawned through script can be pretty reliably differentiated from natives ones through said metadata. In addition to that, we also have a bunch of other checks to verify an entity was spawned through script. All of the objects spawned by the framework are spawned on the server-side, so if you are found spawning an object on the client-side, you had to have injected some script or used a menu to do so.",
            "PED_SPAWN"                => "Very unlikely to be scuff. Every ped in the game has a bunch of metadata attached to it. Peds spawned through script can be pretty reliably differentiated from natives ones through said metadata. In addition to that, we also have a bunch of other checks to verify an entity was spawned through script. All of the peds spawned by the framework are spawned on the server-side, so if you are found spawning a ped on the client-side, you had to have injected some script or used a menu to do so.",
            "VEHICLE_SPAWN"            => "Very unlikely to be scuff. Every vehicle in the game has a bunch of metadata attached to it. Vehicles spawned through script can be pretty reliably differentiated from natives ones through said metadata. In addition to that, we also have a bunch of other checks to verify an entity was spawned through script. All of the vehicles spawned by the framework are spawned on the server-side, so if you are found spawning a vehicle on the client-side, you had to have injected some script or used a menu to do so.",
            "FAST_MOVEMENT"            => "Very unlikely to be scuff. This detection tracks how quickly you are moving around. If you've moved very far within a single game tick for example you had to have teleported. There is a bunch of ways this can occur natively though, for example when falling through the ground, etc. However, we have checks and exceptions for all of those cases.",
            "BLACKLISTED_COMMAND"      => "Very unlikely to be scuff. We have a list of commands that are commonly used by mod menus. Things like /killmenu, etc. for example. If you run one of those commands this detection will trigger. Since anyone could type those commands this detection has the possibility of triggering a false positive, however all of the commands are very specific to mod menus and are very unlikely to be typed by accident.",
            "DISTANCE_TAZE"            => "Very unlikely to be scuff. All tasers used in-game have a very limited range. A lot of mod menus give modders the ability to tase someone much further away. If you are found tasing someone from a distance that is impossible to do normally, you most likely did so through script or a menu. Since the server can lag, there is a very small chance for this to be a false positive but we allow for quite a bit of leeway, so it is incredibly unlikely.",
            "SEMI_GODMODE"             => "Very unlikely to be scuff. To avoid detection, a lot of mod menus offer so called semi-godmode. Compared to straight up invincibility, this works by continuously resetting your health and/or armor to full. So every time you receive damage, you would be healed almost instantly. We track if your health does not decrease when taking damage. If you are continuously not taking damage, you are most likely using semi-godmode.",
            "INFINITE_AMMO"            => "Very unlikely to be scuff. We track how much ammo should be in your weapons magazine. Every time you shoot we subtract from that value. If you shoot a lot more bullets than are in your magazine without reloading in between, you are most likely using some kind of menu to give yourself infinite ammo.",

            "BAD_SCREEN_WORD"          => "Unlikely to be scuff. Every 5 or so seconds, we take a screenshot of your game. If you have a menu on your screen for example, it would be visible in that screenshot. Those screenshots are then analyzed by AI to extract any visible text. We have a huge wordlist of common words and phrases used in mod menus (Things like \"Godmode\", \"Give Money\", etc.). If any of those words are found in the screenshot, you are most likely using a menu. Since the game is open world and supposed to mimic real life, there are things like traffic signs, billboards, etc. that could potentially contain those words. However, we have a bunch of checks and exceptions to avoid triggering false positives. The attached screenshot should be pretty clear if it is or isn't a mod menu.",
        ],
    ];

    const AC_EVENT_MAP = [
        "SUSPICIOUS_EXPLOSION"     => "suspicious_explosion",
        "CLEAR_TASKS"              => "clear_tasks",
        "DISTANCE_TAZE"            => "distance_taze",
        "HIGH_DAMAGE"              => "high_damage",
        "HONEYPOT"                 => "honeypot",
        "SPECTATING"               => "spectating",
        "RUNTIME_TEXTURE"          => "runtime_texture",
        "PED_CHANGE"               => "ped_change",
        "WEAPON_SPAWN"             => "illegal_weapon",
        "DAMAGE_MODIFIER"          => "damage_modifier",
        "VEHICLE_MODIFICATION"     => "vehicle_modification",
        "THERMAL_NIGHTVISION"      => "thermal_night_vision",
        "BLACKLISTED_COMMAND"      => "blacklisted_command",
        "TEXT_ENTRY"               => "text_entry",
        "PLAYER_BLIPS"             => "player_blips",
        "INVINCIBILITY"            => "invincibility",
        "FAST_MOVEMENT"            => "fast_movement",
        "UNDERGROUND"              => "underground",
        "ILLEGAL_FREEZE"           => "illegal_freeze",
        "ILLEGAL_VEHICLE_MODIFIER" => "illegal_vehicle_modifier",
        "BAD_SCREEN_WORD"          => "bad_screen_word",
        "FREECAM"                  => "freecam_detected",
        "SPIKED_RESOURCE"          => "spiked_resource",
        "HOTWIRE_DRIVING"          => "driving_hotwire",
        "SEMI_GODMODE"             => "semi_godmode",
        "INVALID_HEALTH"           => "invalid_health",
        "ILLEGAL_NATIVE"           => "illegal_native",
        "INFINITE_AMMO"            => "infinite_ammo",
        "BAD_ENTITY_SPAWN"         => "spawned_object",
        "PED_SPAWN"                => "illegal_ped_spawn",
        "VEHICLE_SPAWN"            => "illegal_vehicle_spawn",
    ];

    public static function getAutomatedReasons()
    {
        if (self::$automatedReasons === null) {
            self::$automatedReasons = json_decode(file_get_contents(__DIR__ . '/../helpers/automated-bans.json'), true);
        }

        return self::$automatedReasons;
    }

    /**
     * Gets the date that the ban expires.
     *
     * @return Carbon
     */
    public function getExpireAtAttribute(): ?Carbon
    {
        if (is_null($this->expire)) {
            return null;
        }
        return Date::createFromTimestamp($this->timestamp->getTimestamp() + $this->expire);
    }

    public function getExpireTimeInSeconds(): ?int
    {
        return $this->expire;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp->getTimestamp();
    }

    public static function generateHash(): string
    {
        $words = json_decode(file_get_contents(__DIR__ . '/../helpers/human-words.json'), true);

        $choices = [];

        while (true) {
            $wordOne = $words[array_rand($words)];
            $wordTwo = $words[array_rand($words)];
            $wordThree = $words[array_rand($words)];

            $hash = $wordOne . '-' . $wordTwo . '-' . $wordThree;

            if (in_array($hash, $choices)) {
                continue;
            }

            $choices[] = $hash;

            if (count($choices) >= 20) {
                $unavailable = Ban::query()->select(['ban_hash'])->whereIn('ban_hash', $choices)->get()->toArray();

                $available = array_values(array_diff($choices, array_column($unavailable, 'ban_hash')));

                if (!empty($available)) {
                    return $available[0];
                }

                $choices = [];
            }
        }
    }

    /**
     * Checks if the ban has expired.
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        return is_null($this->expireAt)
        ? false
        : $this->expireAt->isPast();
    }

    /**
     * Gets the player relationship.
     *
     * @return BelongsTo
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'license_identifier', 'identifier');
    }

    /**
     * Gets the issuer relationship.
     *
     * @return BelongsTo
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'creator_name', 'player_name');
    }

    public static function resolveAutomatedReason(?string $originalReason): array
    {
        if ($originalReason) {
            $reasons = self::getAutomatedReasons();

            $parts = explode('-', $originalReason);

            $category = array_shift($parts);
            $key      = array_shift($parts);

            if ($reasons && $category && $key && isset($reasons[$category]) && isset($reasons[$category][$key])) {
                $reason = $reasons[$category][$key];

                $info = isset(self::SYSTEM_INFO[$category]) && isset(self::SYSTEM_INFO[$category][$key]) ? self::SYSTEM_INFO[$category][$key] : false;

                return [
                    "reason" => str_replace('${DATA}', implode('-', $parts), $reason) . " (" . $originalReason . ")",
                    "info"   => $info,
                ];
            }
        }

        return [
            "reason" => $originalReason,
            "info"   => false,
        ];
    }

    /**
     * Returns a formatted reason if the ban was automated
     *
     * @return string
     */
    public function getFormattedReason(): array
    {
        if ($this->creator_name) {
            return [
                "reason" => $this->reason ?? '',
                "info"   => false,
            ];
        }

        return self::resolveAutomatedReason($this->reason);
    }

    public static function getBanForUser(string $licenseIdentifier): ?array
    {
        if (empty(self::$bans)) {
            $ban = Ban::query()
                ->where('identifier', '=', $licenseIdentifier)
                ->select(['id', 'ban_hash', 'identifier', 'creator_name', 'reason', 'timestamp', 'expire', 'creator_identifier', 'locked'])
                ->first();
            return $ban ? $ban->toArray() : null;
        }

        return self::$bans[$licenseIdentifier] ?? null;
    }

    /**
     * Returns all banned License Identifiers which were banned by a certain person
     *
     * @param string $creatorName
     * @param string $creatorIdentifier
     * @return array
     */
    public static function getAllBannedIdentifiersByCreator(string $creatorName, string $creatorIdentifier): array
    {
        $bans = self::getAllBans(false);

        return array_values(array_map(function ($ban) {
            return $ban['identifier'];
        }, array_filter($bans, function ($ban) use ($creatorName, $creatorIdentifier) {
            return $ban['creator_name'] === $creatorName || $ban['creator_identifier'] === $creatorIdentifier;
        })));
    }

    public static function getAllBans(bool $returnOnlyIdentifiers, ?array $filterByIdentifiers = null, bool $forceObject = false): array
    {
        if (empty(self::$bans)) {
            $query = Ban::query()
                ->select(['id', 'ban_hash', 'identifier', 'creator_name', 'reason', 'timestamp', 'expire', 'creator_identifier']);

            if ($filterByIdentifiers === null) {
                $query->where('identifier', 'LIKE', 'license:%');
            } else {
                $query->whereIn('identifier', $filterByIdentifiers);
            }

            $bans = $query->orderBy('timestamp')
                ->groupBy('identifier')
                ->get()->toArray();

            foreach ($bans as $ban) {
                self::$bans[$ban['identifier']] = $ban;
            }
        }

        $bans = self::$bans;
        if ($filterByIdentifiers !== null) {
            $bans = array_filter($bans, function ($ban) use ($filterByIdentifiers) {
                return in_array($ban['identifier'], $filterByIdentifiers);
            });
        }

        if ($returnOnlyIdentifiers) {
            return array_keys($bans);
        }

        if ($forceObject && empty($bans)) {
            return ['empty' => 'object'];
        }

        return $bans;
    }

    public static function find(string $hash): ?string
    {
        $key  = "ban_hash_list";
        $bans = false;

        if (CacheHelper::exists($key)) {
            $bans = CacheHelper::read($key) ?? false;
        }

        if (!$bans) {
            $list = self::query()
                ->select(["ban_hash", "identifier"])
                ->where(DB::raw("SUBSTRING_INDEX(identifier, ':', 1)"), '=', 'license')
                ->groupBy("ban_hash")
                ->get();

            $bans = [];

            foreach ($list as $entry) {
                $bans[$entry->ban_hash] = $entry->identifier;
            }

            CacheHelper::write($key, $bans, CacheHelper::HOUR * 6);
        }

        return $bans[$hash] ?? null;
    }
}
