<?php

use App\Ban;
use App\Helpers\CacheHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\SessionHelper;
use App\Session;
use App\Warning;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

function runQuery(string $cluster, string $query)
{
    $dir = realpath(__DIR__ . "/../envs/" . $cluster);
    $env = $dir . "/.env";

    if (empty($env) || !file_exists($env)) {
        return [false, "Failed to read .env file"];
    }

    $contents = file_get_contents($env);

    $dotenv  = Dotenv::createImmutable($dir, ".env");
    $envData = $dotenv->parse($contents);

    $dbName = "cluster_" . $cluster;

    Config::set("database.connections." . $dbName, [
        "driver"   => $envData["DB_CONNECTION"],
        "host"     => $envData["DB_HOST"],
        "port"     => $envData["DB_PORT"],
        "database" => $envData["DB_DATABASE"],
        "username" => $envData["DB_USERNAME"],
        "password" => $envData["DB_PASSWORD"],
    ]);

    try {
        /**
         * @var \Illuminate\Database\Connection
         */
        $conn = DB::connection($dbName);

        $conn->getPdo();
    } catch (\Exception $e) {
        return [false, "Failed to connect to database: " . $e->getMessage()];
    }

    $affected = 0;

    if (Str::startsWith($query, "SELECT")) {
        $affected = DB::connection($dbName)->select($query);

        $affected = count($affected);
    } else if (Str::startsWith($query, "UPDATE")) {
        $affected = DB::connection($dbName)->update($query);
    } else if (Str::startsWith($query, "INSERT")) {
        $affected = DB::connection($dbName)->insert($query);
    } else if (Str::startsWith($query, "DELETE")) {
        $affected = DB::connection($dbName)->delete($query);
    } else {
        return [false, "Unknown query type"];
    }

    return [true, "Affected " . $affected . " rows"];
}

function stopTime($time): string
{
    return round(microtime(true) - $time, 2) . "s" . PHP_EOL;
}

// UPDATE `inventories` SET `item_name` = "weapon_addon_hk416" WHERE `item_name` = "weapon_addon_m4"
Artisan::command("run-query", function () {
    $query = trim($this->ask("SQL Query"));

    if (empty($query)) {
        $this->error("Query is empty");

        return;
    }

    $this->info("Iterating through all clusters...");

    $dir = __DIR__ . "/../envs";

    $clusters = array_diff(scandir($dir), [".", ".."]);

    chdir(__DIR__ . "/..");

    foreach ($clusters as $cluster) {
        $cluster = trim($cluster);

        $path = $dir . "/" . $cluster;

        if (empty($cluster) || !is_dir($path)) {
            continue;
        }

        $this->info("Running query on cluster `" . $cluster . "`...");

        $result = runQuery($cluster, $query);

        if (!$result[0]) {
            $this->error(" - " . $result[1]);
        } else {
            $this->comment(" - " . $result[1]);
        }
    }

    return;
})->describe("Runs a query on all clusters.");

Artisan::command("cron", function () {
    $this->info(CLUSTER . " Running cronjobs...");

    $start = microtime(true);
    echo "Getting log actions...";
    CacheHelper::getLogActions(true);

    echo stopTime($start);

    $start = microtime(true);
    echo "Cleaning up sessions...";
    SessionHelper::cleanup();

    echo stopTime($start);

    $start = microtime(true);
    echo "Cleaning up log files...";
    LoggingHelper::cleanup();

    echo stopTime($start);

    $start = microtime(true);
    echo "Removing scheduled bans...";
    $time = time();

    $bans = Ban::query()
        ->where('scheduled_unban', '<=', $time)
        ->select(["user_id", "ban_hash"])
        ->leftJoin("users", "license_identifier", "=", "identifier")
        ->whereNotNull("user_id")
        ->whereNotNull("ban_hash")
        ->get();

    $toBeDeleted = [];

    foreach ($bans as $ban) {
        $id = $ban->user_id;

        Warning::query()->create([
            'player_id'      => $id,
            'warning_type'   => 'system',
            'can_be_deleted' => 0,
            'message'        => 'I removed this players ban. (Scheduled unban)',
        ]);

        $toBeDeleted[] = $ban->ban_hash;
    }

    if (!empty($toBeDeleted)) {
        Ban::query()->whereIn("ban_hash", $toBeDeleted)->delete();
    }

    echo stopTime($start);

    // Auto-delete non-locked bans after 2+ years
    if (env('AUTO_EXPIRE_BANS')) {
        $start = microtime(true);
        echo "Auto-removing old bans...";

        $bans = Ban::query()
            ->where('locked', '=', 0)
            ->whereNull('expire')
            ->where('timestamp', '<', time() - (60 * 60 * 24 * 365 * 2))
            ->select(["ban_hash", "identifier", "reason"])
            ->get()->toArray();

        $logs        = [];
        $toBeDeleted = [];

        foreach ($bans as $ban) {
            $hash = $ban['ban_hash'];
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
                $dir = dirname($path);

                if (!is_dir($dir)) mkdir($dir, 0775, true);

                file_put_contents($path, implode("\n", $logs) . "\n", FILE_APPEND);
            }
        }

        echo stopTime($start);
    }
})->describe("Runs all cronjobs for a certain cluster.");

Artisan::command("migrate-trunks", function () {
    $this->info(CLUSTER . " Loading inventories...");

    $inventories = DB::select("SELECT * FROM inventories WHERE inventory_name LIKE 'trunk-%' GROUP BY inventory_name");

    $this->info(CLUSTER . " Parsing " . sizeof($inventories) . " inventories...");

    $ids = [];

    $vehicleInventories = [];

    $npcs = 0;

    foreach ($inventories as $inventory) {
        $name = $inventory->inventory_name;

        $parts = explode("-", $name);

        if (sizeof($parts) !== 3) {
            continue;
        }

        if (preg_match('/[^0-9]/', $parts[2])) {
            $npcs++;

            continue;
        }

        $class = intval($parts[1]);
        $id    = intval($parts[2]);

        $vehicleInventories[$id] = [
            "class" => $class,
            "name"  => $name,
        ];

        $ids[] = $id;
    }

    $this->info(CLUSTER . " Skipped $npcs npc trunks...");

    if (empty($ids)) {
        $this->info(CLUSTER . " No inventories to migrate...");

        return;
    }

    $this->info(CLUSTER . " Loading " . sizeof($ids) . " vehicles...");

    $vehicles = DB::table("character_vehicles")->whereIn("vehicle_id", $ids)->get();

    $alphaModels = [
        -2137348917 => "phantom",
        -956048545  => "taxi",
        1162065741  => "rumpo",
        1353720154  => "flatbed",
    ];

    $classes = json_decode(file_get_contents(__DIR__ . "/../helpers/vehicle_classes.json"), true);

    $this->info(CLUSTER . " Parsing " . sizeof($vehicles) . " vehicles...");

    $update = [];
    $alpha  = [];

    $skipped = 0;

    foreach ($vehicles as $vehicle) {
        $id    = intval($vehicle->vehicle_id);
        $model = $vehicle->model_name;

        if (!isset($vehicleInventories[$id])) {
            $skipped++;

            continue;
        }

        if (is_numeric($model)) {
            $model = intval($model);

            $model = $alphaModels[$model] ?? null;

            if (!$model) {
                $skipped++;

                continue;
            }

            $alpha[intval($vehicle->model_name)] = $model;
        }

        $expected = $classes[$model] ?? null;

        if (!$expected && $expected !== 0) {
            $expected = 22;
        }

        $wasName = $vehicleInventories[$id]["name"];
        $isName  = "trunk-" . $expected . "-" . $id;

        if ($wasName === $isName) {
            continue;
        }

        $update[$wasName] = $isName;
    }

    if ($skipped > 0) {
        $this->info(CLUSTER . " Skipped $skipped vehicles...");
    }

    if (!empty($alpha)) {
        if ($this->confirm(CLUSTER . " Found " . sizeof($alpha) . " alpha model hashes, do you want to update them?", false)) {
            foreach ($alpha as $old => $new) {
                $this->info(CLUSTER . " Updating alpha hash $old to $new...");

                DB::update("UPDATE character_vehicles SET model_name = ? WHERE model_name = ?", [$new, $old]);
            }
        }
    }

    $size = sizeof($update);

    if ($size > 0) {
        if (!$this->confirm(CLUSTER . " Found $size affected inventories, continue?", false)) {
            $this->info(CLUSTER . " Aborted!");

            return;
        }

        $this->info(CLUSTER . " Updating $size inventories...");

        $index = 1;

        foreach ($update as $was => $is) {
            echo "$was ($index/$size)          \r";

            DB::update("UPDATE inventories SET inventory_name = ? WHERE inventory_name = ?", [$is, $was]);

            $index++;
        }

        $this->info(CLUSTER . " Finished updating $size inventories.                    ");
    } else {
        $this->info(CLUSTER . " No inventories to update.");
    }

    return;
})->describe("Update all trunks to have the correct vehicle class.");

Artisan::command("repair", function () {
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

    $root = realpath(__DIR__ . "/../");
    $storage = $root . "/storage";
    $cache = $root . "/bootstrap/cache";

    $permissions = [
        "chown -R www-data:www-data $root",
        "find $root -type f -exec chmod 664 {} \;",
        "find $root -type d -exec chmod 775 {} \;",
        "chgrp -R www-data $storage $cache",
        "chmod -R ug+rwx $storage $cache"
    ];

    foreach ($permissions as $permission) {
        $this->comment(" - $permission");

        exec($permission);
    }

    $this->info(CLUSTER . " Done!");
})->describe("Attempt to repair general laravel issues.");

Artisan::command("clear:sessions", function () {
    $this->info(CLUSTER . " Dropping all sessions...");

    $count = Session::query()->delete();

    $this->info(CLUSTER . " Dropped $count sessions.");
})->describe("Drop all laravel sessions.");
