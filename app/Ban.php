<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'locked' => 'boolean',
        'timestamp' => 'datetime',
    ];

	private static $automatedReasons = null;

    const SYSTEM_INFO = [
        "MODDING" => [
            "BAD_ENTITY_SPAWN" => "Basically impossible to be scuff. This detection has been refined over the years and has become incredibly accurate. It used to have a bunch of false positives but the way it functions has since been changed. It is basically impossible to trigger this detection accidentally now, since it requires the spawned object to be script owned, meaning it has to be spawned through script. The framework will either not network spawned objects (spawn them only for you) or spawn them on the server.",
            "SPECTATING" => "Impossible to be scuff. This requires them to enable spectator mode which can only be done through script. Our spectator mode does not use the native GTA spectator mode. If they are spectating, they are undoubtedly cheating.",
            "PED_CHANGE" => "Basically impossible to be scuff. It requires them to change their ped. We change player peds through script quite often, but we track that every time we do it and ignore it.",
            "FAST_MOVEMENT" => "Basically impossible to be scuff. This detection has been reworked. It originally tracked people no-clipping but is now focused entirely on teleporting. We keep track of any time a player is legitimately teleported and ignore those cases. If they teleport through a mod menu for example this detection would kick in and ban them. We also track the distance they teleported, which can be found in the metadata.",
            "INVINCIBILITY" => "Basically impossible to be scuff. This requires them to enable invincibility which can only be done through script. Every time we do it through script we track it and ignore that case. This has yet to have a single false positive.",
            "RUNTIME_TEXTURE" => "Impossible to be scuff. This requires them to create a certain runtime texture. Some mod menus use runtime textures to display images like logos or headers in their menu. Those menus usually use the same texture so its very easy to detect. It does not occur naturally in any way and requires script.",
            "VEHICLE_SPAWN" => "Almost impossible to be a false positive. For this detection to be triggered the client has to spawn a vehicle through script. Sometimes this gets triggered randomly though, however when that happens it only happens once. We cancel all spawn attempts, meaning the vehicle will never actually be spawned. If the player is actually trying to spawn a vehicle, their client will attempt to spawn it multiple times very quickly since we cancel their spawn attempts, compared to the known false positives which give up after the first try. This makes it relatively easy to detect and differentiate between false positives and actual spawn attempts. All framework spawned vehicles are spawned on the server and not the client.",
            "VEHICLE_MODIFICATION" => "Very unlikely to be a false positive. It requires you to change the modifications of the vehicle you are currently driving. That can only happen through script and all the cases where it does happen we track and ignore. This detection has not had a false positive in forever.",
            "WEAPON_SPAWN" => "Used to be pretty unreliable but has been refined over time and is now very unlikely to be a false positive. We track all cases where we give the player a weapon through script. The only other case this can be a false positive is if a local drops their gun when they die, but we have a check for that exact case. Usually the attached screen capture is also very obvious.",
            "THERMAL_NIGHTVISION" => "Basically impossible to be scuff. This doesn't occur naturally and requires script. We track all cases where we give the player thermal/night vision and ignore those cases.",
            "BLACKLISTED_COMMAND" => "Very unlikely to be scuff. There is a variety of commands that mod menus use to do certain things. When a player runs such a command they get banned. It could happen that they just randomly managed to type the command but the command names are usually very obscure and not something you would type by accident.",
            "TEXT_ENTRY" => "Impossible to be scuff. This requires a script to set a certain text entry. GTA uses text entries to display text in certain situations for example the onscreen keyboard. We track only a selected list of text entries which are used by mod menus. If they set one of those text entries they get banned.",
            "PLAYER_BLIPS" => "Impossible to be scuff. This requires them to have a blip on the map for each player. Blips can only be created through script and in this specific type of blip is not even used anywhere in the entire framework.",
            "VEHICLE_SPAM" => "Basically impossible to be scuff. This requires them to spawn a lot of vehicles in a short period of time. This can only be done through script and we track all cases where we spawn vehicles through script and ignore those cases. You can find the type of vehicle they spawned in the metadata.",
            "PED_SPAWN" => "Almost impossible to be a false positive. For this detection to be triggered the client has to spawn a ped through script. Sometimes this gets triggered randomly though, however when that happens it only happens once. We cancel all spawn attempts, meaning the ped will never actually be spawned. If the player is actually trying to spawn a ped, their client will attempt to spawn it multiple times very quickly since we cancel their spawn attempts, compared to the known false positives which give up after the first try. This makes it relatively easy to detect and differentiate between false positives and actual spawn attempts. All framework spawned peds are spawned on the server and not the client.",
            "DAMAGE_MODIFIER" => "Basically impossible to be scuff. We use damage modifiers to set the damage for weapons in the framework. Every time someone shoots we compare the current damage modifier with the one its supposed to be set to. If they are different we know they had to have changed it since the only way to set it is through script. We also reset it back to its default value every we detect that its off, so if they get banned it means they had some kind of script that continuously set the modifier.",
            "ILLEGAL_FREEZE" => "Basically impossible to be scuff. This requires them to freeze their player ped which does not occur anywhere naturally. We track all cases where this happens in the framework and ignore them. Freezing the player ped is usually used in features like noclip or teleporting. To prevent the player from falling.",
            "ILLEGAL_VEHICLE_MODIFIER" => "Very unlikely to be scuff. All the false positives should be ironed out by now. This requires them to set or modify a certain vehicle modifier. Usually used to increase traction or acceleration. We track all cases where we set vehicle modifiers in the framework and ignore them.",
            "BAD_SCREEN_WORD" => "Could be scuff. This detection scans the players screen for certain trigger words to detect mod menus. We ignore all text that we display on the screen through the framework but sometimes a shop name in the game or an ad on the side of a vehicle can contain one of those trigger words. If you are unsure if its a false positive or not, check the attached screen capture. Usually the amount of trigger words is also a good indicator.",
            "FREECAM" => "Basically impossible to be scuff. This requires them to create a camera through script. Everywhere we do this in the framework we track it and ignore it. You can only create cameras through script and they are usually used to things like freecam or recently also setting FOV modifiers. This detection has not had a false positive.",
            "SPIKED_RESOURCE" => "Impossible to be scuff. This detection scans all of our resources to detect players injecting their own scripts into them. When injecting scripts you need a resource to inject into. We track all of the framework resources to make sure they don't do that.",
            "HOTWIRE_DRIVING" => "Basically impossible to be scuff. We have a shit load of checks for this one to ensure that its not a false positive. This requires them to have the hotwire text on their screen, have the engine on, be moving forwards and have all 4 wheels touch the ground. We have not had a single false positive with this detection. The attached screen-capture is also usually very obvious.",
            "DISTANCE_TAZE" => "Very unlikely to be scuff. This requires them to taze a player over a very large distance. Tasers have a very very limited range so it is basically impossible to taze someone much further than said range. You can also emulate a taser through script which is what a variety of mod menus use to spam taze other players.",
            "HIGH_DAMAGE" => "Very unlikely to be scuff. A bunch of mod menus have a feature allowing the player to apply super high damage to players around them to instantly kill them or similar. We have a list of every single weapon and the maximum damage it is able to apply. If a player attempts to apply more damage than the maximum damage of the weapon they are using, they get banned.",
            "SUSPICIOUS_EXPLOSION" => "Basically impossible to be scuff. This requires them to create an explosion of a specific type, through script. Every time said explosion can occur naturally we have checks for. They also need to create the explosions a bunch of times in a short period of time to be actually banned for it. The metadata will include a bunch more information like how far away they were and how many other players would have been affected/killed by the explosion. It does not have a screenshot attached to prevent further damage from being done.",
            "SEMI_GODMODE" => "Very unlikely to be scuff. This detection checks if someone continuously resets their health or armor back to full. This can only be done through script and every time we do so, we track and ignore the case. This detection also has a shit load of additional checks to make sure we don't get false positives.",
            "INVALID_HEALTH" => "Impossible to be scuff. This requires them to modify the maximum health of their ped. That can only be done through script and we set every peds health to be maximum 200.",
            "HONEYPOT" => "Impossible to be scuff. We have a bunch of so called 'honeypots' in the framework. They are functions that have lucrative names such as 'stealCash' or 'spawnItem' and when they are executed they will trigger a ban. That basically means someone inspected the code, searched for this function and then manually ran it, so its literally impossible to be scuff.",
            "CLEAR_TASKS" => "Impossible to be scuff. A bunch of mod menus clear other players tasks to kick them out of their vehicle for example. Clearing a peds tasks will make them stop doing what they are currently doing basically. This does not occur naturally and only happens through script. The framework will never attempt to clear another players tasks.",
        ]
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
		$getHash = function() {
			$letters = 'abcdefghijklmnopqrstuvwxyz0123456789';

			$hash = '';

			for ($i = 0; $i < 8; $i++) {
				$hash .= $letters[rand(0, strlen($letters) - 1)];
			}

			return $hash;
		};

		while (true) {
			$hash = $getHash();

			if (!Ban::query()->where('ban_hash', '=', $hash)->exists()) {
				return $hash;
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

	public static function resolveAutomatedReason(string $originalReason): array
	{
		$reasons = self::getAutomatedReasons();

		$parts = explode('-', $originalReason);

		$category = array_shift($parts);
		$key = array_shift($parts);

		if ($reasons && $category && $key && isset($reasons[$category]) && isset($reasons[$category][$key])) {
			$reason = $reasons[$category][$key];

            $info = isset(self::SYSTEM_INFO[$category]) && isset(self::SYSTEM_INFO[$category][$key]) ? self::SYSTEM_INFO[$category][$key] : false;

			return [
                "reason" => str_replace('${DATA}', implode('-', $parts), $reason) . " (" . $originalReason . ")",
                "info" => $info
            ];
		}

		return [
            "reason" => $originalReason,
            "info" => false
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
                "info" => false
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
}
