<?php
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

use App\Helpers\GeneralHelper;

include_once __DIR__ . '/functions.php';

date_default_timezone_set('UTC');

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
 * This all for multi-instance support
 */
define('DOCKER', !!getenv('DOCKER_MODE'));

if (DOCKER) {
    define('CLUSTER', env('DOCKER_CLUSTER', 'c1'));
} else {
    if (!defined('CLUSTER')) {
        define('CLUSTER', GeneralHelper::getCluster());
    }

    $envDir = realpath(__DIR__ . '/../envs/' . CLUSTER);

    if (file_exists($envDir) && CLUSTER !== null) {
        $app->useEnvironmentPath($envDir);
    } else if (php_sapi_name() !== 'cli') {
        die('Invalid cluster "' . CLUSTER . '"');
    }
}

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
