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
        $path    = realpath(sprintf("%s/../storage/base/%s.log", __DIR__, $cluster));

        $directory = dirname($path);

        if (! file_exists($directory)) {
            mkdir($directory, 0644, true);
        }

        $payload = sprintf("%s\n", implode("\n", self::$logs));

        file_put_contents($path, $payload, FILE_APPEND);
    }

    public static function access(Request $request)
    {
        self::register();

        self::$logs[] = self::format(sprintf(
            "%s %s",
            $request->getMethod(),
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
        $message = $format;

        if (! empty($data)) {
            $message = sprintf($format, ...$data);
        }

        return sprintf(
            "[%s] %s - %s",
            date("Y-m-d H:i:s"),
            self::license(),
            $message,
        );
    }

    private static function license(): string
    {
        if (php_sapi_name() === "cli") {
            return "cli";
        }

        if (! function_exists("license")) {
            return "unk";
        }

        return substr(license(), 8);
    }
}
