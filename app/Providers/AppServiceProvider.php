<?php
namespace App\Providers;

use App\Helpers\GeneralHelper;
use App\Helpers\JwtHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\SocketAPI;
use App\Http\Resources\LoggedInPlayerResource;
use App\Server;
use App\Warning;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
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
        // Ensure Jwt helper is initialized
        JwtHelper::init();

        // Disable resource wrapping.
        JsonResource::withoutWrapping();

        Blade::directive('vite', function () {
            if (app()->environment('local') && file_exists(public_path('hot'))) {
                return '
                    <script type="module" src="http://localhost:5173/@vite/client"></script>
                    <script type="module" src="http://localhost:5173/resources/js/app.js"></script>
                    <link rel="stylesheet" href="http://localhost:5173/resources/css/app.pcss" />
                ';
            }

            $base         = 'build/';
            $manifestPath = public_path($base . 'manifest.json');

            if (! file_exists($manifestPath)) {
                return '<!-- manifest not found -->';
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (! $manifest || ! is_array($manifest)) {
                return '<!-- manifest unreadable -->';
            }

            $import = [];

            foreach ($manifest as $file) {
                if (empty($file) || empty($file['isEntry'])) {
                    continue;
                }

                $src = $base . $file['file'];

                if (Str::endsWith($src, '.js')) {
                    $import[] = '<script type="module" src="/' . $src . '"></script>';

                    foreach ($file['css'] ?? [] as $stylesheet) {
                        $import[] = '<link rel="stylesheet" href="/' . $base . $stylesheet . '" />';
                    }
                } elseif (Str::endsWith($src, '.css')) {
                    $import[] = '<link rel="stylesheet" href="/' . $src . '" />';
                }
            }

            if (empty($import)) {
                return '<!-- nothing to import -->';
            }

            return implode('', $import);
        });

        $canUseDB = ! app()->runningInConsole() && env('DB_CONNECTION');

        $discord = $canUseDB ? JwtHelper::get('discord') : null;
        $name    = $discord ? $discord['username'] : 'Guest';

        DB::listen(function ($query) use ($name) {
            if (! env('LOG_QUERIES') || ! CLUSTER) {
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
        $server = Server::getFirstServer();

        // Shared inertia data.
        Inertia::share([
            // Current and previous url.
            'url'        => Str::start(str_replace(url('/'), '', URL::current()), '/'),
            'back'       => Str::start(str_replace(url('/'), '', URL::previous('/')), '/'),

            // Flash messages.
            'flash'      => function () {
                $success = session_get('flash_success');
                $error   = session_get('flash_error');

                session_forget('flash_success');
                session_forget('flash_error');

                return [
                    'success' => $success,
                    'error'   => $error,
                ];
            },

            'docker' => DOCKER,

            'serverIp'   => $server ? $server['ip'] : null,
            'serverName' => $server ? $server['name'] : null,
            'connect'    => function () {
                return Server::getConnectUrl();
            },

            'discord'    => function () {
                return discord();
            },

            'overwatch'  => function () {
                if (PermissionHelper::hasPermission(PermissionHelper::PERM_SCREENSHOT)) {
                    $url = env('OVERWATCH_URL', '');

                    $streams = array_map(function ($stream) use ($url) {
                        return sprintf($url, trim($stream));
                    }, explode(',', env('OVERWATCH_STREAMS', '')));

                    $streams = array_values(array_filter($streams));

                    if (! empty($streams)) {
                        return [
                            'streams' => $streams,
                        ];
                    }
                }

                return false;
            },

            'emotes'     => Warning::getAllReactions(),

            'global'     => env('GLOBAL_SERVER', 'https://global.op-framework.com/'),
            'api'        => env('API_SERVER', 'https://op-framework.com/api'),

            // Authentication.
            'auth'       => function () {
                $player = user();

                return [
                    'player'      => $player ? new LoggedInPlayerResource($player) : null,
                    'settings'    => $player ? $player->getPanelSettings() : null,
                    'permissions' => PermissionHelper::getFrontendPermissions(),
                    'cluster'     => CLUSTER,
                    'socket'      => DOCKER || SocketAPI::isUp(),
                ];
            },

            'lang'       => env('VUE_APP_LOCALE', 'en-us'),
        ]);
    }
}
