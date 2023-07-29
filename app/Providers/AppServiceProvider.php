<?php

namespace App\Providers;

use App\Helpers\PermissionHelper;
use App\Http\Resources\PlayerResource;
use App\Server;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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

		DB::listen(function ($query) {
			// Disable query logging
		});
    }

    /**
     * Registers inertia.
     */
    protected function registerInertia()
    {
        // Shared inertia data.
        Inertia::share([
            // Current and previous url.
            'url'   => Str::start(str_replace(url('/'), '', URL::current()), '/'),
            'back'  => Str::start(str_replace(url('/'), '', URL::previous('/')), '/'),

            // Flash messages.
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error'   => session('error'),
                ];
            },

            'serverIp' => Server::getFirstServerIP(),

            'discord' => function() {
                $session = sessionHelper();

                return $session->get('discord') ?: null;
            },

            // Authentication.
            'auth'  => function () {
                $player = user();

                return [
                    'player'      => $player ? new PlayerResource($player) : null,
                    'permissions' => PermissionHelper::getFrontendPermissions(),
                    'token'       => sessionKey(),
                    'cluster'     => CLUSTER,
                    'server'      => Server::getServerName(Server::getFirstServer()),
                    'servers'     => Server::getAllServerNames(),
                ];
            },

            'lang' => env('VUE_APP_LOCALE', 'en-us'),
        ]);
    }

}
