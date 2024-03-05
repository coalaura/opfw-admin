<?php
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command"s IO methods.
|
 */

$disabledProdCommands = [
    'ui',
    'tinker',

    // db:*
    'db:seed',
    'db:wipe',

    // make:*
    'make:cast',
    'make:channel',
    'make:command',
    'make:component',
    'make:controller',
    'make:event',
    'make:exception',
    'make:factory',
    'make:job',
    'make:listener',
    'make:mail',
    'make:middleware',
    'make:migration',
    'make:model',
    'make:notification',
    'make:observer',
    'make:policy',
    'make:provider',
    'make:request',
    'make:resource',
    'make:rule',
    'make:seeder',
    'make:test',

    // migrate:*
    'migrate:fresh',
    'migrate:install',

    // session:*
    'session:table',

    // stub:*
    'stub:publish',

    // ui:*
    'ui:auth',
    'ui:controllers',

    // vendor:*
    'vendor:publish',
];

if ('production' === App::environment()) {
    foreach ($disabledProdCommands as $command) {
        Artisan::command($command, function () {
            $this->comment('This command is disabled in production.');
        })->describe('Disabled in production.');
    }
}
