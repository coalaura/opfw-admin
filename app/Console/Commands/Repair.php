<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Repair extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repair';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Attempt to repair general laravel issues.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info(CLUSTER . " Clearing caches...");

        $caches = [
            "cache:clear",
            "view:clear",
            "config:clear",
            "event:clear",
            "route:clear",
        ];

        foreach ($caches as $cache) {
            $this->comment(" - $cache");

            Artisan::call($cache);
        }

        $this->info(CLUSTER . " Repairing permissions...");

        $root    = realpath(__DIR__ . "/../");
        $storage = $root . "/storage";
        $cache   = $root . "/bootstrap/cache";

        $permissions = [
            "chown -R www-data:www-data $root",
            "find $root -type d \( -name .git -o -name vendor -o -name node_modules \) -prune -o -type f -print0 | xargs -0 chmod 664",
            "find $root -type d \( -name .git -o -name vendor -o -name node_modules \) -prune -o -type d -print0 | xargs -0 chmod 775",
            "chgrp -R www-data $storage $cache",
            "chmod -R ug+rwx $storage $cache",

            // Fix .git ownership
            "chown -R \$USER:\$USER $root/.git",
        ];

        foreach ($permissions as $permission) {
            $this->comment(" - $permission");

            exec($permission);
        }

        $this->info(CLUSTER . " Done!");

        return 0;
    }
}
