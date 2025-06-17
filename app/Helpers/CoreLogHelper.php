<?php
namespace App\Helpers;

use Illuminate\Http\Request;

class CoreLogHelper
{
    private static $registered = false;
    private static $logs       = [];

    private static function register()
    {
        if (self::$registered) {
            return;
        }

        self::$registered = false;

        register_shutdown_function(function () {
            self::shutdown();
        });
    }

    private static function shutdown()
    {
        if (empty(self::$logs)) {
            return;
        }

        $cluster = (defined("CLUSTER") ? CLUSTER : "core") ?? "core";
        $path    = storage_path(sprintf("base/%s.log", $cluster));

        $directory = dirname($path);

        if (! file_exists($directory)) {
            mkdir($directory, 0775, true);
        }

        $payload = sprintf("%s\n", implode("\n", self::$logs));

        file_put_contents($path, $payload, FILE_APPEND);
    }

    public static function access(Request $request)
    {
        $method = $request->getMethod();

        if ($method === "GET" && $request->isXmlHttpRequest()) {
            return;
        }

        self::register();

        self::$logs[] = self::format(sprintf(
            "%s (%d) %s",
            $method,
            $request->header("Content-Length", 0),
            $request->getPathInfo(),
        ));
    }

    public static function log(string $format, ...$data)
    {
        self::register();

        self::$logs[] = self::format($format, ...$data);
    }

    private static function format(string $format, ...$data): string
    {
        $date    = date("Y-m-d\TH:i:s.v");
        $message = $format;

        if (! empty($data)) {
            $message = sprintf($format, ...$data);
        }

        return sprintf(
            "[%s] %s - %s",
            $date,
            self::user(),
            $message,
        );
    }

    private static function user(): string
    {
        if (php_sapi_name() === "cli") {
            return "cli";
        }

        if (! function_exists("user")) {
            return "unk";
        }

        $user = user();

        return $user ? dechex($user->user_id) : "unk";
    }
}
