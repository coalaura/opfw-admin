<?php

namespace App\Console\Commands;

use App\Ban;
use App\Helpers\CacheHelper;
use App\Helpers\HttpHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\ServerAPI;
use App\Helpers\SessionHelper;
use App\Server;
use App\Warning;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class Cronjobs extends Command
{
    const StaticJsonAPIs = [
        [ServerAPI::class, 'getItems'],
        [ServerAPI::class, 'getVehicles'],
        [ServerAPI::class, 'getWeapons'],
        [ServerAPI::class, 'getJobs'],
        [ServerAPI::class, 'getDefaultJobs'],
        [ServerAPI::class, 'getChatEmotes'],
        [ServerAPI::class, 'getRoutes'],
        [ServerAPI::class, 'getCrafting'],
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs all cronjobs for a certain cluster.';

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
        $this->info(CLUSTER . " Testing database connection...");

        try {
            DB::select("SELECT 1");
        } catch (QueryException $e) {
            $this->warn(sprintf("Failed to connect to database: %s", $e->getMessage()));

            return;
        }

        $this->info(CLUSTER . " Running cronjobs...");

        $start = microtime(true);
        echo " - Getting log actions...";
        CacheHelper::getLogActions(true);

        echo $this->stopTime($start);

        $start = microtime(true);
        echo " - Cleaning up sessions...";
        SessionHelper::cleanup();

        echo $this->stopTime($start);

        $start = microtime(true);
        echo " - Cleaning up log files...";
        LoggingHelper::cleanup();

        echo $this->stopTime($start);

        $start = microtime(true);
        echo " - Removing scheduled bans...";
        $time = time();

        $bans = Ban::query()
            ->where('scheduled_unban', '<=', $time)
            ->select(["user_id", "ban_hash"])
            ->leftJoin("users", "license_identifier", "=", "identifier")
            ->whereNotNull("ban_hash")
            ->get();

        $toBeDeleted = [];

        foreach ($bans as $ban) {
            $id = $ban->user_id;

            if (!empty($id)) {
                Warning::query()->create([
                    'player_id'      => $id,
                    'warning_type'   => 'system',
                    'can_be_deleted' => 0,
                    'message'        => 'I removed this players ban. (Scheduled unban)',
                ]);
            }

            $toBeDeleted[] = $ban->ban_hash;
        }

        if (!empty($toBeDeleted)) {
            Ban::query()->whereIn("ban_hash", $toBeDeleted)->delete();
        }

        echo $this->stopTime($start);

        // Auto-delete non-locked bans after 2+ years
        if (env('AUTO_EXPIRE_BANS')) {
            $start = microtime(true);
            echo " - Auto-removing old bans...";

            $bans = Ban::query()
                ->where('locked', '=', 0)
                ->whereNull('expire')
                ->where('timestamp', '<', time() - (60 * 60 * 24 * 365 * 2))
                ->select(["ban_hash", "identifier", "reason"])
                ->get()->toArray();

            $logs        = [];
            $toBeDeleted = [];

            foreach ($bans as $ban) {
                $hash       = $ban['ban_hash'];
                $identifier = $ban['identifier'];

                if (Str::startsWith($identifier, "steam:") || Str::startsWith($identifier, "license:")) {
                    $reason = preg_replace('/\s+/', ' ', $ban['reason']);

                    $logs[] = sprintf('[%s] %s: %s - "%s"', date('Y-m-d H:i:s'), $hash, $identifier, $reason);
                }

                if (!in_array($hash, $toBeDeleted)) {
                    $toBeDeleted[] = $hash;
                }
            }

            if (!empty($toBeDeleted)) {
                Ban::query()->whereIn("ban_hash", $toBeDeleted)->delete();

                if (!empty($logs)) {
                    $path = storage_path('bans/' . CLUSTER . '.log');
                    $dir  = dirname($path);

                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }

                    file_put_contents($path, implode("\n", $logs) . "\n", FILE_APPEND);
                }
            }

            echo $this->stopTime($start);
        }

        // Refresh static json APIs
        $start = microtime(true);
        echo " - Checking if FiveM server is reachable...";

        $reachable = HttpHelper::ping(Server::getFirstServer(), 2000);

        echo $this->stopTime($start);

        if ($reachable) {
            echo " - Refreshing static json APIs:" . PHP_EOL;

            ServerAPI::forceRefresh();

            foreach (self::StaticJsonAPIs as $api) {
                call_user_func($api);
            }

            Server::getConnectUrl(true);
        } else {
            echo " - FiveM server is not reachable, skipping static json API refresh." . PHP_EOL;
        }
    }

    private function stopTime($time): string
    {
        return round(microtime(true) - $time, 2) . "s" . PHP_EOL;
    }
}
