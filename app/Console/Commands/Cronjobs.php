<?php
namespace App\Console\Commands;

use App\Ban;
use App\Helpers\CacheHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\ServerAPI;
use App\PanelLog;
use App\Server;
use App\Warning;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Cronjobs extends Command
{
    const StaticJsonAPIs = [
        [ServerAPI::class, 'getItems'],
        [ServerAPI::class, 'getVehicles'],
        [ServerAPI::class, 'getVehiclesTxt'],
        [ServerAPI::class, 'getWeapons'],
        [ServerAPI::class, 'getPeds'],
        [ServerAPI::class, 'getJobs'],
        [ServerAPI::class, 'getDefaultJobs'],
        [ServerAPI::class, 'getChatEmotes'],
        [ServerAPI::class, 'getRoutes'],
        [ServerAPI::class, 'getPermissions'],
        [ServerAPI::class, 'getCrafting'],
        [ServerAPI::class, 'getConfig'],
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
        LoggingHelper::disable();

        if (env('INACTIVE', false)) {
            $this->warn(CLUSTER . " cluster is marked as inactive.");

            return;
        }

        $this->info(CLUSTER . " Testing database connection...");

        $connName = DB::getDefaultConnection();
        $optKey   = "database.connections.{$connName}.options";
        $original = config($optKey, []);

        config([$optKey => $original + [\PDO::ATTR_TIMEOUT => 2]]);

        DB::purge($connName);

        try {
            DB::select("SELECT 1");
        } catch (QueryException $e) {
            $this->warn(sprintf("Failed to connect to database: %s", $e->getMessage()));

            return;
        } finally {
            config([$optKey => $original]);

            DB::purge($connName);
        }

        $this->info(CLUSTER . " Running cronjobs...");

        $start = microtime(true);
        echo " - Getting log actions...";
        CacheHelper::getLogActions(true);

        echo $this->stopTime($start);

        $start = microtime(true);
        echo " - Cleaning up log files...";
        LoggingHelper::cleanup();

        echo $this->stopTime($start);

        $start = microtime(true);
        echo " - Cleaning up panel logs...";
        PanelLog::cleanup();

        echo $this->stopTime($start);

        $start = microtime(true);
        echo " - Removing scheduled bans...";
        $time = time();

        $bans = Ban::query()
            ->where('scheduled_unban', '<=', $time)
            ->select(["user_id", "ban_hash", "identifier", "reason"])
            ->leftJoin("users", "license_identifier", "=", "identifier")
            ->whereNotNull("ban_hash")
            ->get();

        $logs        = [];
        $toBeDeleted = [];

        foreach ($bans as $ban) {
            $id = $ban->user_id;

            if (! empty($id)) {
                Warning::query()->create([
                    'player_id'      => $id,
                    'warning_type'   => 'system',
                    'can_be_deleted' => 0,
                    'message'        => 'I removed this players ban. (Scheduled unban)',
                ]);
            }

            $reason = preg_replace('/\s+/', ' ', $ban->reason);
            $logs[] = sprintf('[%s] %s: %s - "%s"', date('Y-m-d H:i:s'), $ban->ban_hash, $ban->identifier, $reason);

            $toBeDeleted[] = $ban->ban_hash;
        }

        if (! empty($toBeDeleted)) {
            Ban::query()->whereIn("ban_hash", $toBeDeleted)->delete();

            $this->dumpBanLogs("bans", "sch", $logs);
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

                if (! in_array($hash, $toBeDeleted)) {
                    $toBeDeleted[] = $hash;
                }
            }

            if (! empty($toBeDeleted)) {
                Ban::query()->whereIn("ban_hash", $toBeDeleted)->delete();

                $this->dumpBanLogs("bans", "exp", $logs);
            }

            echo $this->stopTime($start);
        }

        // Refresh static json APIs
        $start = microtime(true);
        echo " - Checking if FiveM server is reachable...";

        $reachable = ! empty(ServerAPI::getVariables());

        echo $this->stopTime($start);

        if ($reachable) {
            echo " - Refreshing static json APIs:" . PHP_EOL;

            ServerAPI::forceRefresh();

            foreach (self::StaticJsonAPIs as $api) {
                $start = microtime(true) * 1000;

                $result = call_user_func($api);

                if (! $result || empty($result)) {
                    $this->warn(sprintf(" - Failed to refresh %s (empty)", $api[1]));
                } else {
                    $taken = round(microtime(true) * 1000 - $start);

                    $this->info(sprintf(" - Refreshed %s in %dms: %s", $api[1], $taken, self::string($result)));
                }
            }

            Server::getConnectUrl(true);
        } else {
            echo " - FiveM server is not reachable, skipping static json API refresh." . PHP_EOL;
        }

        // Fix broken permissions
        $start = microtime(true);
        echo " - Repairing file permissions...";

        system("chown -R www-data:www-data storage");
        system("chgrp -R www-data storage");
        system("chmod -R ug+rwx storage");

        echo $this->stopTime($start);
    }

    private function dumpBanLogs(string $category, string $type, array $logs)
    {
        if (empty($logs)) {
            return;
        }

        $path = storage_path(sprintf("%s/%s_%s.log", $category, CLUSTER, $type));
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, implode("\n", $logs) . "\n", FILE_APPEND);
    }

    private function stopTime($time): string
    {
        return round(microtime(true) - $time, 2) . "s" . PHP_EOL;
    }

    private function string($value): string
    {
        switch (gettype($value)) {
            case 'boolean':
                return sprintf('bool(%s)', $value ? 'true' : 'false');
            case 'integer':
                return sprintf('int(%s)', $value);
            case 'double':
                return sprintf('float(%s)', $value);
            case 'string':
                return sprintf('string(%s)', strlen($value));
            case 'array':
                return sprintf('array(%s)', count($value));
            case 'object':
                return sprintf('object(%s)', get_class($value));
            default:
                return sprintf('unknown(%s)', gettype($value));
        }
    }
}
