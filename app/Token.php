<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;

    const ValidMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'REST'];

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
        if (!$this->permissions) return [];

        return self::stringToPermissions($this->permissions);
    }

    public static function stringToPermissions(string $permissions): array
    {
        $result = [];

        $routes = explode(',', $permissions);

        foreach ($routes as $route) {
            $parts = explode(' ', $route);

            if (sizeof($parts) !== 2) {
                continue;
            }

            $result[] = [
                'method' => $parts[0],
                'path'  => $parts[1],
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
            $path = trim($permission['path']);

            if (!in_array($method, self::ValidMethods) || empty($path)) {
                continue;
            }

            $result[] = $method . ' ' . $path;
        }

        return implode(',', $result);
    }

    public static function permissionsValid(?string $permissions): bool
    {
        if (!$permissions) return true;

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
}
