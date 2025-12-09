<?php
namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A panel action that has been logged.
 *
 * @package App
 */
class PanelLog extends Model
{
    use HasFactory;

    const Actions = [
        "Added Vehicle",
        "Changed Ped Model",
        "Created Notification",
        "Deleted Character",
        "Deleted Notification",
        "Deleted Vehicle",
        "Divorced Characters",
        "Edited Balance",
        "Edited Character",
        "Edited Licenses",
        "Edited Permissions",
        "Enabled Ban Exception",
        "Kicked Player",
        "Muted Player",
        "Refreshed E-Mail",
        "Removed Ban Exception",
        "Removed Ban",
        "Removed Tag",
        "Removed Tattoos",
        "Reset Spawn",
        "Revived Offline",
        "Revived Player",
        "Staff PM",
        "Un-Whitelisted Player",
        "Unlinked HWID",
        "Unlinked Identifier",
        "Unloaded Character",
        "Unmuted Player",
        "Updated Tag",
        "Whitelisted Player",
    ];

    /**
     * Column name for when the model was created.
     */
    const CREATED_AT = 'timestamp';

    /**
     * Column name for when the model was last updated.
     */
    const UPDATED_AT = 'timestamp';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webpanel_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identifier',
        'action',
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
     * Removes all panel logs older than 1 month
     */
    public static function cleanup()
    {
        self::query()->where('timestamp', '<=', Carbon::now()->subMonths(6))->delete();
    }

    /**
     * Creates a new panel log.
     */
    public static function log(string $license, string $action, string $details, ?array $metadata = null)
    {
        self::create([
            'identifier' => $license,
            'action'     => $action,
            'details'    => $details,
            'metadata'   => $metadata,
            'timestamp'  => Carbon::now(),
        ]);
    }
}
