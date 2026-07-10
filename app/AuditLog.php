<?php
namespace App;

use App\Helpers\LoggingHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An audit log entry recording a panel mutation performed by a staff member.
 *
 * Replaces the deprecated PanelLog system. PanelLog remains read-only for
 * historical data; all new mutations are logged through AuditLog.
 *
 * @package App
 */
class AuditLog extends Model
{
    /**
     * Column name for when the model was created.
     */
    const CREATED_AT = 'timestamp';

    /**
     * Column name for when the model was last updated. Audit logs are
     * immutable, so this mirrors CREATED_AT to satisfy Eloquent.
     */
    const UPDATED_AT = 'timestamp';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * Whether the model should be timestamped. Handled manually.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'license',
        'action',
        'target_type',
        'target_id',
        'details',
        'metadata',
        'timestamp',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metadata'  => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Known target types. Used for validation/filtering in the UI; not
     * enforced on writes so that ad-hoc values remain possible.
     */
    const TargetTypes = [
        'player',
        'character',
        'vehicle',
        'inventory',
        'ban',
        'savings_account',
        'company',
        'warning',
        'blacklist',
        'token',
        'yell',
        'y_user',
        'loading_screen_image',
    ];

    /**
     * Removes all audit logs older than 6 months.
     */
    public static function cleanup()
    {
        self::query()->where('timestamp', '<=', Carbon::now()->subMonths(6))->delete();
    }

    /**
     * Creates a new audit log entry.
     *
     * @param string             $license     License identifier of the acting staff member.
     * @param string             $action      Dotted action key with exactly one dot, e.g. "player.mute".
     * @param string|null        $targetType  Optional target type (see TargetTypes).
     * @param string|int|null    $targetId    Optional target identifier (license, char id, plate, etc.).
     * @param string             $details     Human-readable summary of the action. Required.
     * @param array|null         $metadata    Optional structured context (before/after, reason, etc.).
     * @return self
     */
    public static function log(string $license, string $action, ?string $targetType = null, $targetId = null, string $details = '', ?array $metadata = null): ?self
    {
        try {
            return self::create([
                'license'     => $license,
                'action'      => $action,
                'target_type' => $targetType,
                'target_id'   => $targetId !== null ? (string) $targetId : null,
                'details'     => $details,
                'metadata'    => $metadata,
                'timestamp'   => Carbon::now(),
            ]);
        } catch (\Throwable $t) {
            LoggingHelper::log(sprintf("Failed to create audit log: %s", $t->getMessage()));
        }

        return null;
    }

    /**
     * Gets the player relationship (the actor).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function player()
    {
        return $this->belongsTo(Player::class, 'license', 'license_identifier');
    }
}
