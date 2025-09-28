<?php
namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A character made by a player.
 *
 * @package App
 */
class Character extends Model
{
    use HasFactory;

    const BloodTypes = [
        "O+"     => "The most common blood type. O+ individuals can donate red blood cells to any Rh-positive blood type.",
        "A+"     => "The second most common blood type. A+ individuals can donate red blood cells to A+ and AB+ recipients.",
        "B+"     => "A fairly common blood type. B+ individuals can donate red blood cells to B+ and AB+ recipients.",
        "AB+"    => "The rarest of the 'common' blood types. Known as the universal plasma donor, but AB+ individuals can only donate red blood cells to other AB+ recipients.",
        "O-"     => "A universal donor of red blood cells, O- can be given to any blood type. It is often used in emergency situations.",
        "A-"     => "A relatively rare blood type. A- individuals can donate red blood cells to A-, A+, AB-, and AB+ recipients.",
        "B-"     => "A rare blood type. B- individuals can donate red blood cells to B-, B+, AB-, and AB+ recipients.",
        "AB-"    => "The rarest standard blood type. AB- individuals can donate red blood cells to AB- and AB+ recipients.",
        "Rhnull" => "Known as 'Golden Blood,' it lacks all Rh antigens. Extremely rare and highly valuable for transfusion in compatible individuals.",
        "Bombay" => "Lacks the H antigen found in all other blood types. Bombay individuals can only receive blood from other Bombay donors.",
        "Cis-AB" => "A rare genetic mutation where A and B antigens are inherited on a single allele. Compatible with some A, B, AB, and O types, but requires careful matching.",
    ];

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
    protected $primaryKey = 'character_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'license_identifier',
        'character_slot',
        'gender',
        'first_name',
        'last_name',
        'date_of_birth',
        'blood_type',
        'backstory',
        'is_dead',
        'cash',
        'bank',
        'stocks_balance',
        'job_name',
        'department_name',
        'position_name',
        'character_created',
        'character_creation_timestamp',
        'character_deleted',
        'character_deletion_timestamp',
        'character_creation_time',
        'last_loaded',
        'ped_model_hash',
        'tattoos_data',
        'coords',
        'character_data',
        'email_address',
        'married_to',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_slot'               => 'integer',
        'gender'                       => 'integer',
        'cash'                         => 'integer',
        'bank'                         => 'integer',
        'character_creation_time'      => 'integer',
        'stocks_balance'               => 'double',
        'character_created'            => 'boolean',
        'character_creation_timestamp' => 'datetime',
        'character_deleted'            => 'boolean',
        'character_deletion_timestamp' => 'datetime',
        'coords'                       => 'array',
        'weekly_playtime'              => 'array',
        'character_data'               => 'array',
        'tattoos_data'                 => 'array',
    ];

    /**
     * @var array
     */
    private static $cache = [];

    /**
     * Gets the full name by concatenating first name and last name together.
     *
     * @return string
     */
    protected function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Gets the total amount of money by adding cash and bank together.
     *
     * @return int
     */
    protected function getMoneyAttribute(): int
    {
        return $this->cash + $this->bank;
    }

    protected function getBloodTypeAttribute(): ?array
    {
        $type = $this->attributes['blood_type'];

        if (! isset(self::BloodTypes[$type])) {
            return null;
        }

        return [
            'name' => $type,
            'info' => self::BloodTypes[$type],
        ];
    }

    public static function getOutfits(int $characterId, bool $includePreviews = false): array
    {
        $fields = ['name'];

        if ($includePreviews) {
            $fields[] = 'showcase_url';
        }

        return DB::table('outfits')
            ->select($fields)
            ->where('character_id', '=', $characterId)
            ->get()
            ->toArray();
    }

    /**
     * @param int $characterId
     * @return Character|null
     */
    public static function find(int $characterId): ?self
    {
        if (! isset(self::$cache[$characterId])) {
            self::$cache[$characterId] = self::query()->where('character_id', '=', $characterId)->first();
        }

        return self::$cache[$characterId];
    }

    /**
     * Gets player relationship.
     *
     * @return BelongsTo
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'license_identifier', 'license_identifier');
    }

    /**
     * Gets the vehicles owned by this character.
     *
     * @return HasMany
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'owner_cid')->where('vehicle_deleted', '=', '0');
    }

    /**
     * Gets the properties owned by this character.
     *
     * @return HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'property_renter_cid');
    }

    /**
     * Gets the properties this character has access to.
     *
     * @return HasMany
     */
    public function accessProperties()
    {
        return Property::query()->select()->where("shared_keys", "LIKE", "%-" . $this->character_id . ";%")->get();
    }

    /**
     * Returns all licenses
     *
     * @return array
     */
    public function getLicenses(): array
    {
        $json = $this->character_data ?? [];

        if (! isset($json['licenses']) || ! is_array($json['licenses'])) {
            return [];
        }

        return $json['licenses'];
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

    /**
     * Returns a map of character_id->[character_name,licenseIdentifier]
     * This is used instead of a left join as it appears to be a lot faster
     *
     * @param array $source
     * @param string $sourceKey
     * @return array
     */
    public static function fetchIdNameMap(array $source, string $sourceKey): array
    {
        $ids = [];
        foreach ($source as $entry) {
            if (! in_array($entry[$sourceKey], $ids)) {
                $ids[] = $entry[$sourceKey];
            }
        }

        $characters = self::query()->whereIn('character_id', $ids)->select([
            'character_id', 'license_identifier', 'first_name', 'last_name',
        ])->get();
        $characterMap = [];
        foreach ($characters as $character) {
            $characterMap[$character->character_id] = [
                'license_identifier' => $character->license_identifier,
                'name'               => $character->first_name . ' ' . $character->last_name,
            ];
        }

        if (empty($characterMap)) {
            $characterMap['empty'] = 'empty';
        }

        return $characterMap;
    }

    public function refreshEmailAddress(): bool
    {
        $current = $this->email_address;

        $firstName = splitAlphaNum(strtolower($this->first_name));
        $lastName  = splitAlphaNum(strtolower($this->last_name));

        if (! $firstName || ! $lastName) {
            return false;
        }

        $counter = 0;
        $email   = sprintf("%s.%s", $firstName, $lastName);

        if ($email === $current) {
            return true;
        }

        while (self::query()->where('email_address', '=', $email)->count() > 0) {
            $counter++;

            $email = sprintf("%s.%s%d", $firstName, $lastName, $counter);
        }

        $this->update([
            'email_address' => $email,
        ]);

        return true;
    }

}
