<?php

namespace App\Providers;

use App\Helpers\GeneralHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\SessionHelper;
use App\Http\Resources\PlayerResource;
use App\Server;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register inertia.
        $this->registerInertia();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Disable resource wrapping.
        JsonResource::withoutWrapping();

        $canUseDB = !app()->runningInConsole() && env('DB_CONNECTION');

        $discord = $canUseDB ? SessionHelper::getInstance()->getDiscord() : null;
        $name    = $discord ? $discord['username'] : 'Guest';

        DB::listen(function ($query) use ($name) {
            if (!env('LOG_QUERIES') || !CLUSTER) {
                return;
            }

            $time = date("H:i:s");
            $day  = date("Y_m_d");

            $file = storage_path("logs/" . CLUSTER . "_query_{$day}.log");

            $sql = $query->sql;

            foreach ($query->bindings as $binding) {
                if (is_string($binding)) {
                    $first = substr($binding, 0, 1);
                    $last  = substr($binding, -1);

                    if ($first == '{' && $last == '}') {
                        $binding = '{...}';
                    } else if ($first == '[' && $last == ']') {
                        $binding = '[...]';
                    }

                    if (strlen($binding) > 65) {
                        $binding = substr($binding, 0, 65) . '...';
                    }
                }

                $binding = is_numeric($binding) ? $binding : "'{$binding}'";

                $sql = preg_replace('/\?/', "{$binding}", $sql, 1);
            }

            $sql = preg_replace_callback('/in \((.+?)\)/m', function ($matches) {
                $values = explode(',', $matches[1]);

                return 'in (...' . count($values) . ' values...)';
            }, $sql);

            $sql = trim(str_replace("\n", ' ', $sql));

            $log = "[{$time} - {$name}] {$sql} ({$query->time}ms)";

            put_contents($file, $log . "\n", FILE_APPEND);
        });

        if ($canUseDB) {
            Inertia::share([
                'timezones' => GeneralHelper::getCommonTimezones(),
                'update'    => GeneralHelper::isPanelUpdateAvailable(),
            ]);
        }
    }

    /**
     * Registers inertia.
     */
    protected function registerInertia()
    {
        $ip = Server::getFirstServerIP();

        // Shared inertia data.
        Inertia::share([
            // Current and previous url.
            'url'        => Str::start(str_replace(url('/'), '', URL::current()), '/'),
            'back'       => Str::start(str_replace(url('/'), '', URL::previous('/')), '/'),

            // Flash messages.
            'flash'      => function () {
                $helper = sessionHelper();

                $success = $helper->get('flash_success');
                $error   = $helper->get('flash_error');

                $helper->forget('flash_success');
                $helper->forget('flash_error');

                return [
                    'success' => $success,
                    'error'   => $error,
                ];
            },

            'serverIp'   => $ip,
            'serverName' => Server::getServerName($ip),

            'discord'    => function () {
                $session = sessionHelper();

                return $session->get('discord') ?: null;
            },

            'echo'       => env('ECHO_SERVER', null),
            'global'     => env('GLOBAL_SERVER', null),

            // Authentication.
            'auth'       => function () {
                $player = user();

                return [
                    'player'      => $player ? new PlayerResource($player) : null,
                    'settings'    => $player ? $player->getPanelSettings() : null,
                    'permissions' => PermissionHelper::getFrontendPermissions(),
                    'token'       => sessionKey(),
                    'cluster'     => CLUSTER,
                    'server'      => Server::getServerName(Server::getFirstServer()),
                    'servers'     => Server::getAllServerNames(),
                ];
            },

            'lang'       => env('VUE_APP_LOCALE', 'en-us'),
        ]);
    }

}
