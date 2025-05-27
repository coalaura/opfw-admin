<?php
namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Container extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'containers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'container_id';

    /**
     * Whether to use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'access',
        'paid_until',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'paid_until'   => 'integer',
        'access'       => 'array',
    ];

    public static function all($_ = [])
    {
        return Container::query()
            ->select(['container_id', 'paid_until', 'containers.character_id', 'first_name', 'last_name', 'license_identifier'])
            ->leftJoin('characters', 'characters.character_id', '=', 'containers.character_id')
            ->orderBy('container_id', 'asc')
            ->get();
    }

    public static function items()
    {
        return DB::table("inventories")
            ->select(DB::raw("COUNT(inventory_name) as count"), "inventory_name")
            ->where(DB::raw("SUBSTR(inventory_name, 1, 9)"), "=", "container")
            ->groupBy("inventory_name")
            ->get();
    }

    public function access()
    {
        $access = $this->access ?? [];

        return Character::select(["player_name", DB::raw("CONCAT(first_name, ' ', last_name) as full_name"), "character_id", "characters.license_identifier"])
            ->leftJoin("users", "characters.license_identifier", "=", "users.license_identifier")
            ->whereIn("character_id", $access)
            ->get()->toArray();
    }
}
