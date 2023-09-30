<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\SessionHelper;
use App\Server;
use App\Ban;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CronjobController extends Controller
{
    /**
     * General purpose cronjobs
     */
    public function generalCronjob()
    {
        $start = microtime(true);
        echo "Getting log actions...";
        CacheHelper::getLogActions(true);

        echo $this->stopTime($start);

        $start = microtime(true);
        echo "Getting server status...";
        CacheHelper::getServerStatus(Server::getFirstServer(), true);

        echo $this->stopTime($start);

        $start = microtime(true);
        echo "Cleaning up sessions...";
        SessionHelper::cleanup();

        echo $this->stopTime($start);

        $start = microtime(true);
        echo "Removing scheduled bans...";
        Ban::query()->where('scheduled_unban', '<=', time())->delete();

        echo $this->stopTime($start);
    }

    private function stopTime($time): string {
        return round(microtime(true) - $time, 2) . "s" . PHP_EOL;
    }
}
