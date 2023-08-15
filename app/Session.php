<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * An action that has been logged.
 *
 * @package App
 */
class Session extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'key';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webpanel_sessions';

    const IgnorePaths = [
        '_debugbar',
        'api'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'data',
        'last_accessed',
        'last_viewed',
        'ip_address',
        'user_agent'
    ];

    public static function metadata(): array
    {
        $data = [
            'last_accessed' => time()
        ];

        $req = request();

        if ($req) {
            $path = $req->path();

            if (!$req->ajax() && $req->method() === 'GET' && !Str::startsWith($path, self::IgnorePaths)) {
                $data['last_viewed'] = $path;
            }

            $data['ip_address'] = $req->ip();
            $data['user_agent'] = $req->userAgent();
        }

        return $data;
    }
}
