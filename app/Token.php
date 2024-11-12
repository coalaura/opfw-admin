<?php

namespace App;

use App\Helpers\ServerAPI;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Token extends Model
{
    use HasFactory;

    const ValidMethods = ['*', 'REST', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tokens';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'token_id';

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
        'token',
        'permissions',
        'note',
        'total_requests',
        'last_request_timestamp',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'total_requests'         => 'integer',
        'last_request_timestamp' => 'integer',
    ];

    public function getPermissions(): array
    {
        if (!$this->permissions) {
            return [];
        }

        return self::stringToPermissions($this->permissions);
    }

    public static function getRecentLogs(int $tokenId, ?int $beforeId = null, int $limit = 50): array
    {
        $query = DB::table('api_logs')
            ->select(['id', 'token_id', 'ip_address', 'method', 'path', 'status_code', 'timestamp'])
            ->where('token_id', '=', $tokenId)
            ->orderBy('timestamp', 'desc')
            ->limit($limit);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->get()->toArray();
    }

    public static function getRequestsPerSecond(int $tokenId): array
    {
        $interval = 5 * 60;

        $count = DB::table('api_logs')
            ->where('token_id', '=', $tokenId)
            ->where('timestamp', '>', time() - $interval)
            ->count();

        return [
            'count' => $count,
            'rps' => round($count / $interval, 2)
        ];
    }

    public static function stringToPermissions(string $permissions): array
    {
        $available = self::getAvailableRoutes();

        $result = [];

        $routes = explode(',', $permissions);

        foreach ($routes as $route) {
            $parts = explode(' ', $route);

            if (sizeof($parts) !== 2) {
                continue;
            }

            $method = strtoupper($parts[0]);
            $path   = trim($parts[1]);

            $allowed = $available[$method] ?? [];

            if (!in_array($path, $allowed) && $path !== '*') {
                continue;
            }

            $result[] = [
                'method' => $method,
                'path'   => $path,
            ];
        }

        return $result;
    }

    public static function permissionsToString(array $permissions): string
    {
        $result = [];

        foreach ($permissions as $permission) {
            if (!isset($permission['method']) || !isset($permission['path'])) {
                continue;
            }

            $method = strtoupper($permission['method']);
            $path   = trim($permission['path']);

            if (!in_array($method, self::ValidMethods) || empty($path)) {
                continue;
            }

            $result[] = $method . ' ' . $path;
        }

        return implode(',', $result);
    }

    public static function permissionsValid(?string $permissions): bool
    {
        if (!$permissions) {
            return true;
        }

        $data = self::stringToPermissions($permissions);

        return self::permissionsToString($data) === $permissions;
    }

    public static function generateToken(): string
    {
        // Token is a 20 character alphanumeric string
        $token = false;

        while (!$token || Token::where('token', $token)->exists()) {
            $token = bin2hex(random_bytes(10));
        }

        return $token;
    }

    public static function getAvailableRoutes(): array
    {
        $available = [];

        foreach (self::ValidMethods as $method) {
            $available[$method] = [];
        }

        $routes = ServerAPI::getRoutes();

        foreach ($routes as $route) {
            $method = strtoupper($route['method']);
            $path   = $route['path'];

            $available[$method][] = $path;
        }

        foreach ($available as $method => $paths) {
            $paths = array_unique($paths);

            sort($paths);

            $available[$method] = $paths;
        }

        return $available;
    }
}
