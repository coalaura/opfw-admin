<?php
namespace App\Http\Controllers;

use App\Character;
use App\Log;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    const FinancialResources = [
        "diamonds"       => [5000, 6000],
        "gold_watches"   => [1250, 1500],
        "necklaces"      => [500, 600],
        "silver_watches" => [300, 350],
        "gold_bar"       => 1000,

        "raw_emerald"    => [50, 140],
        "raw_sapphire"   => [140, 260],
        "raw_ruby"       => [270, 530],
        "raw_morganite"  => [1400, 2320],

        "emerald"        => [140, 230],
        "sapphire"       => [270, 520],
        "ruby"           => [540, 1000],
        "morganite"      => [2220, 5530],
    ];

    public function logs(Request $request, string $action): Response
    {
        $action = trim($action);

        if (! $action) {
            return self::respond("Empty action!");
        }

        $details = trim($request->input('details'));

        $all = Log::query()
            ->selectRaw('`player_name`, COUNT(`identifier`) as `amount`')
            ->where('action', '=', $action);

        if ($details) {
            $all->where('details', 'LIKE', '%' . $details . '%');
        }

        $all = $all->groupBy('identifier')
            ->leftJoin('users', 'identifier', '=', 'license_identifier')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        $last24hours = Log::query()
            ->selectRaw('`player_name`, COUNT(`identifier`) as `amount`')
            ->where('action', '=', $action);

        if ($details) {
            $last24hours->where('details', 'LIKE', '%' . $details . '%');
        }

        $last24hours = $last24hours->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '>', time() - 24 * 60 * 60)
            ->groupBy('identifier')
            ->leftJoin('users', 'identifier', '=', 'license_identifier')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        $text = self::renderStatistics($action, "24 hours", $last24hours, $details);
        $text .= "\n\n";
        $text .= self::renderStatistics($action, "30 days", $all, $details);

        return self::respond($text);
    }

    private static function renderStatistics(string $type, string $timespan, $rows, $details): string
    {
        $lines = [
            "Top 10 Logs of type `" . $type . "` in the past " . $timespan . ":",
            $details ? "- Details like: `" . $details . "`\n" : "",
        ];

        foreach ($rows as $message) {
            $lines[] = $message->player_name . ': ' . $message->amount;
        }

        return implode("\n", $lines);
    }

    public function smartWatchLeaderboard(): Response
    {
        $all = DB::table('inventories')
            ->select('item_metadata')
            ->where('item_name', '=', 'smart_watch')
            ->get()
            ->toArray();

        $leaderboard = [];

        foreach ($all as $item) {
            $metadata = json_decode($item->item_metadata, true);

            if ($metadata && isset($metadata['firstName']) && isset($metadata['lastName'])) {
                $name = $metadata['firstName'] . ' ' . $metadata['lastName'];

                if (! isset($leaderboard[$name])) {
                    $leaderboard[$name] = [
                        'steps'  => 0,
                        'deaths' => 0,
                        'kills'  => 0,
                    ];
                }

                if (isset($metadata['stepsWalked'])) {
                    $steps = floor(floatval($metadata['stepsWalked']));

                    if ($leaderboard[$name]['steps'] < $steps) {
                        $leaderboard[$name]['steps'] = $steps;
                    }
                }

                if (isset($metadata['deaths'])) {
                    $deaths = intval($metadata['deaths']);

                    if ($leaderboard[$name]['deaths'] < $deaths) {
                        $leaderboard[$name]['deaths'] = $deaths;
                    }
                }

                if (isset($metadata['kills'])) {
                    $kills = intval($metadata['kills']);

                    if ($leaderboard[$name]['kills'] < $kills) {
                        $leaderboard[$name]['kills'] = $kills;
                    }
                }
            }
        }

        $list = [];

        foreach ($leaderboard as $name => $data) {
            $list[] = [
                'name'   => $name,
                'steps'  => $data['steps'],
                'deaths' => $data['deaths'],
                'kills'  => $data['kills'],
            ];
        }

        usort($list, function ($a, $b) {
            return $b['steps'] - $a['steps'];
        });

        $index = 0;

        $stepsList = array_map(function ($entry) use (&$index) {
            $index++;

            return $index . ".\t" . number_format($entry['steps']) . "\t" . $entry['name'];
        }, array_splice($list, 0, 15));

        usort($list, function ($a, $b) {
            return $b['deaths'] - $a['deaths'];
        });

        $index = 0;

        $deathsList = array_map(function ($entry) use (&$index) {
            $index++;

            return $index . ".\t" . number_format($entry['deaths']) . "\t" . $entry['name'];
        }, array_splice($list, 0, 15));

        $index = 0;

        usort($list, function ($a, $b) {
            return $b['kills'] - $a['kills'];
        });

        $killsList = array_map(function ($entry) use (&$index) {
            $index++;

            return $index . ".\t" . number_format($entry['kills']) . "\t" . $entry['name'];
        }, array_splice($list, 0, 15));

        $text = "Top 15 steps traveled\n\nSpot\tSteps\tFull-Name\n" . implode("\n", $stepsList);
        $text .= "\n\n- - -\n\n";
        $text .= "Top 15 deaths\n\nSpot\tDeaths\tFull-Name\n" . implode("\n", $deathsList);
        $text .= "\n\n- - -\n\n";
        $text .= "Top 15 locals murdered\n\nSpot\tKills\tFull-Name\n" . implode("\n", $killsList);

        return self::respond($text);
    }

    public function banLeaderboard(): Response
    {
        $staff = Player::query()->select(["license_identifier", "player_name"])->where("is_staff", "=", "1")->orWhere("is_senior_staff", "=", "1")->orWhere("is_super_admin", "=", "1")->get();

        $max      = 0;
        $staffMap = [];

        foreach ($staff as $player) {
            $staffMap[$player->license_identifier] = $player->player_name;

            if (strlen($player->player_name) > $max) {
                $max = strlen($player->player_name);
            }
        }

        if (strlen("System") > $max) {
            $max = strlen("System");
        }

        // What a chonker
        $query       = "SELECT * FROM (SELECT identifier, creator_identifier, reason, (SELECT SUM(playtime) FROM characters WHERE license_identifier = identifier) as playtime FROM user_bans WHERE identifier LIKE 'license:%' AND creator_identifier IN ('" . implode("', '", array_keys($staffMap)) . "') AND timestamp >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))) bans WHERE playtime IS NOT NULL AND playtime > 0 ORDER BY playtime LIMIT 10";
        $querySystem = "SELECT * FROM (SELECT identifier, creator_identifier, reason, (SELECT SUM(playtime) FROM characters WHERE license_identifier = identifier) as playtime FROM user_bans WHERE identifier LIKE 'license:%' AND creator_name IS NULL AND timestamp >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))) bans WHERE playtime IS NOT NULL AND playtime > 0 ORDER BY playtime LIMIT 1";

        $bans      = DB::select($query);
        $banSystem = DB::select($querySystem);

        $fmt = function ($s) {
            if ($s >= 60) {
                $m = floor($s / 60);
                $s -= $m * 60;

                return $m . "m " . $s . "s";
            }

            return $s . "s";
        };

        $leaderboard = [];

        $banSystem     = $banSystem[0];
        $leaderboard[] = "00. " . str_pad("System", $max, " ") . "  " . $banSystem->identifier . "\t" . $fmt(intval($banSystem->playtime)) . "\t" . ($banSystem->reason ?? "No reason");

        for ($x = 0; $x < sizeof($bans) && $x < 10; $x++) {
            $ban = $bans[$x];

            $name = $staffMap[$ban->creator_identifier] ?? "System";

            $leaderboard[] = str_pad(($x + 1) . "", 2, "0", STR_PAD_LEFT) . ". " . str_pad($name, $max, " ") . "  " . $ban->identifier . "\t" . $fmt(intval($ban->playtime)) . "\t" . ($ban->reason ?? "No reason");
        }

        $bans = DB::select("SELECT COUNT(DISTINCT ban_hash) c, creator_identifier FROM user_bans WHERE SUBSTRING_INDEX(identifier, ':', 1) = 'license' AND timestamp >= " . (strtotime("-3 months")) . " AND (creator_identifier IN ('" . implode("', '", array_keys($staffMap)) . "') OR creator_name IS NULL) GROUP BY creator_identifier ORDER BY c DESC");

        $days = round((time() - strtotime("-3 months")) / 86400);

        $leaderboard2 = [];
        for ($x = 0; $x < sizeof($bans) && $x <= 10; $x++) {
            $ban = $bans[$x];

            $perDay = round($ban->c / $days, 1);

            $name = $staffMap[$ban->creator_identifier] ?? "System";

            $leaderboard2[] = str_pad($x . "", 2, "0", STR_PAD_LEFT) . ". " . str_pad($name, $max, " ") . "  " . str_pad($ban->c . " bans", 10, " ") . " (~" . $perDay . " per day)";
        }

        $text = "Top 10 quickest bans (Last 3 months)\n\n" . implode("\n", $leaderboard) . "\n\n- - -\n\nTop 10 most bans (Last 3 months)\n\n" . implode("\n", $leaderboard2);

        if (isset($_GET["all"])) {
            $bans = DB::select("SELECT COUNT(DISTINCT ban_hash) c, creator_identifier FROM user_bans WHERE SUBSTRING_INDEX(identifier, ':', 1) = 'license' AND (creator_identifier IN ('" . implode("', '", array_keys($staffMap)) . "') OR creator_name IS NULL) GROUP BY creator_identifier ORDER BY c DESC");

            $leaderboard3 = [];
            foreach ($bans as $x => $ban) {
                $name = $staffMap[$ban->creator_identifier] ?? "System";

                $leaderboard3[] = str_pad(($x + 1) . "", 2, "0", STR_PAD_LEFT) . ". " . str_pad($name, $max, " ") . "  " . $ban->c . " bans";
            }

            $text .= "\n\n- - -\n\nTop 10 most bans (All time)\n\n" . implode("\n", $leaderboard3);
        }

        return self::respond($text);
    }

    public function moddingBans(Request $request): Response
    {
        if (! $this->isSuperAdmin($request)) {
            return self::respond('Only super admins can export bans.');
        }

        $keywords = [
            "cheat",
            "modder",
            "modding",
            "script",
            "hacker",
            "hacking",
            "inject",
        ];

        foreach ($keywords as &$word) {
            $word = "reason like \"%" . $word . "%\"";
        }

        $query = "select identifier, reason from user_bans where identifier like \"license:%\" and (" . implode(" or ", $keywords);

        if (CLUSTER === "c3") {
            $query .= " or (reason like \"%1.5%\" and timestamp > 1614553200)";
        }

        $query .= ") GROUP BY identifier ORDER BY timestamp";

        $bans = DB::select($query);

        $fd = fopen('php://temp/maxmemory:1048576', 'w');

        fputcsv($fd, ["license_identifier", "reason"]);

        foreach ($bans as $ban) {
            fputcsv($fd, [$ban->identifier, $ban->reason]);
        }

        rewind($fd);
        $csv = stream_get_contents($fd);
        fclose($fd);

        return (new Response($csv, 200))
            ->header('Content-Type', 'application/octet-stream')
            ->header("Content-Transfer-Encoding", "Binary")
            ->header("Content-disposition", "attachment; filename=\"modders.csv\"");
    }

    public function staffPlaytime(Request $request): Response
    {
        if (! $this->isSuperAdmin($request)) {
            return self::respond('Only super admins can do this.');
        }

        $staff = Player::query()->select(["license_identifier", "player_name", "playtime"])->orWhere("is_staff", "=", "1")->orWhere("is_senior_staff", "=", "1")->orWhere("is_super_admin", "=", "1")->get();

        $entries = [];

        foreach ($staff as $player) {
            $entries[] = [
                'license'  => $player->license_identifer,
                'name'     => $player->player_name,
                'playtime' => intval($player->playtime),
            ];
        }

        usort($entries, function ($a, $b) {
            return $b['playtime'] - $a['playtime'];
        });

        $text = "Staff playtime\n\n";

        foreach ($entries as $entry) {
            $seconds = $entry['playtime'];

            $minutes = floor($seconds / 60);
            $seconds -= $minutes * 60;

            $hours = floor($minutes / 60);
            $minutes -= $hours * 60;

            $time = str_pad($hours . "h " . $minutes . "m " . $seconds . "s", 12);

            $text .= $time . " - " . $entry['name'] . " (" . $entry['license'] . ")\n";
        }

        return self::respond($text);
    }

    public function jobApi(Request $request, string $api_key, string $jobName, string $departmentName, string $positionName, string $characterIds): Response
    {
        if (env('DEV_API_KEY', '') !== $api_key || empty($api_key) || $api_key === "some_random_token") {
            return (new Response('Unauthorized', 403))->header('Content-Type', 'text/plain');
        }

        $characterIds = explode(',', $characterIds);

        if (empty($characterIds)) {
            return (new Response('No character_ids provided', 400))->header('Content-Type', 'text/plain');
        }

        $characters = Character::query()
            ->select(["license_identifier", "character_id", "job_name", "department_name", "position_name", "first_name", "last_name"])
            ->whereIn('character_id', $characterIds)
            ->orWhere(function ($query) use ($jobName, $departmentName, $positionName) {
                return $query->where('job_name', $jobName)
                    ->where('department_name', $departmentName)
                    ->where('position_name', $positionName);
            })
            ->get()->toArray();

        return (new Response(json_encode($characters), 200))->header('Content-Type', 'application/json');
    }

    public function finance(Request $request): Response
    {
        $data  = DB::select(DB::raw("SELECT SUM(cash + bank + stocks_balance) as total_money FROM characters"));
        $money = floor($data[0]->total_money);

        $data = DB::select(DB::raw("SELECT SUM(amount) as total_shared from shared_accounts"));
        $money += floor($data[0]->total_shared);

        $data = DB::select(DB::raw("SELECT SUM(company_balance) as total_stocks FROM stocks_companies"));
        $money += floor($data[0]->total_stocks);

        $data      = DB::select(DB::raw("SELECT SUM(1) as count, item_name FROM inventories WHERE item_name IN ('" . implode("', '", array_keys(self::FinancialResources)) . "') GROUP BY item_name"));
        $resources = 0;

        foreach ($data as $item) {
            $price = self::FinancialResources[$item->item_name];

            if (is_array($price)) {
                $price = ($price[0] + $price[1]) / 2;
            }

            $resources += $price * $item->count;
        }

        $text = [
            "In circulation: $" . number_format($money),
            "In valuables:   $" . number_format($resources),
            "",
            "Total:          $" . number_format($money + $resources),
        ];

        return self::respond(implode("\n", $text));
    }

    public function badScreenText(Request $request, string $api_key): Response
    {
        if (env('DEV_API_KEY', '') !== $api_key || empty($api_key) || $api_key === "some_random_token") {
            return self::json(false, null, 'Unauthorized');
        }

        $data = DB::select(DB::raw("SELECT JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.text')) as text FROM anti_cheat_events LEFT JOIN user_bans ON identifier = license_identifier WHERE type = 'bad_screen_word' AND JSON_EXTRACT(metadata, '$.text') IS NOT NULL AND ban_hash IS NOT NULL"));

        $bad = array_values(array_map(function ($item) {
            return $item->text;
        }, $data));

        $data = DB::select(DB::raw("SELECT JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.text')) as text FROM anti_cheat_events LEFT JOIN user_bans ON identifier = license_identifier WHERE type = 'bad_screen_word' AND JSON_EXTRACT(metadata, '$.text') IS NOT NULL AND ban_hash IS NULL"));

        $good = array_values(array_map(function ($item) {
            return $item->text;
        }, $data));

        return self::json(true, [
            'bad'  => $bad,
            'good' => $good,
        ]);
    }

    public function staffActivity()
    {
        $after = time() - (60 * 60 * 24 * 30);

        $data = DB::select("SELECT player_name, creator_identifier, timestamp FROM user_bans LEFT JOIN users ON license_identifier = creator_identifier WHERE is_staff = 1 AND SUBSTRING(identifier, 1, 8) = 'license:' AND timestamp > $after");

        $bans = [];

        foreach ($data as $item) {
            $name      = $item->player_name;
            $timestamp = $item->timestamp;

            if (! isset($bans[$name])) {
                $bans[$name] = [
                    'time'  => 0,
                    'count' => 0,
                ];
            }

            $bans[$name]['time'] = max($bans[$name]['time'], $timestamp);
            $bans[$name]['count']++;
        }

        $list = [];

        foreach ($bans as $name => $item) {
            $list[] = $name . " had " . $item['count'] . " bans in the last 30 days. Last ban was " . date('m/d/Y', $item['time']);
        }

        return self::respond(implode("\n", $list));
    }

    public function staffActivity2()
    {
        $after = time() - (60 * 60 * 24 * 30);

        $data = DB::select("SELECT player_name, UNIX_TIMESTAMP(created_at) as timestamp FROM warnings LEFT JOIN users ON users.user_id = issuer_id WHERE is_staff = 1 AND warning_type != 'system' AND created_at > $after");

        $daily    = [];
        $averages = [];
        $notes    = [];

        foreach ($data as $item) {
            $name      = $item->player_name;
            $timestamp = $item->timestamp;

            $day = date('m/d/Y', $timestamp);

            $count = $notes[$name] ?? 0;

            $notes[$name] = $count + 1;

            if (! isset($daily[$day])) {
                $daily[$day] = [];
            }

            $dayCount = $daily[$day][$name] ?? 0;

            $daily[$day][$name] = $dayCount + 1;

            if (! isset($averages[$name])) {
                $averages[$name] = [];
            }
        }

        for ($i = $after; $i < time(); $i += (60 * 60 * 24)) {
            $day = date('m/d/Y', $i);

            $count = $daily[$day] ?? [];

            foreach ($averages as $name => $item) {
                $total = $count[$name] ?? 0;

                $averages[$name][] = $total;
            }
        }

        $list = [];

        foreach ($notes as $name => $count) {
            $average = $averages[$name] ?? [];

            $average = empty($average) ? 0 : array_sum($average) / count($average);

            $list[] = $name . " had " . $count . " notes in the last 30 days, with an average of " . round($average, 2) . " per day.";
        }

        return self::respond(implode("\n", $list));
    }

    public function userStatistics(Request $request, Player $player)
    {
        if (! $this->isSuperAdmin($request)) {
            abort(403);
        }

        $statistics = $player->getUserStatistics();
        $lines      = [
            sprintf("User statistics for %s", $player->getSafePlayerName()),
        ];

        foreach ($statistics as $name => $value) {
            $lines[] = sprintf("%s: %s", $name, $value['value']);
        }

        return self::respond(implode("\n", $lines));
    }

    public function nancyStatistics()
    {
        $logs = Log::query()
            ->selectRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'lost ', -1), ' when respawning.', 1) as lost")
            ->where('action', '=', 'Respawn Loot')
            ->where(DB::raw("RIGHT(details, 25)"), '!=', 'anything when respawning.')
            ->get()->toArray();

        $lost = [];

        foreach ($logs as $log) {
            $items = explode(', ', $log['lost']);

            foreach ($items as $item) {
                preg_match('/^(\d+)x (.+)$/', $item, $matches);

                if (count($matches) === 3) {
                    $count = intval($matches[1]);
                    $name  = $matches[2];

                    $lost[$name] = ($lost[$name] ?? 0) + $count;
                }
            }
        }

        $list = [];

        foreach ($lost as $name => $count) {
            $list = [
                'name' => $name,
                'count' => $count,
            ];
        }

        usort($list, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return self::respond(implode("\n", array_map(function($item) {
            return sprintf("%dx %s", $item['count'], $item['name']);
        }, $list)));
    }

    public function test(Request $request): Response
    {
        $license = license();

        if ($license !== "license:2ced2cabd90f1208e7e056485d4704c7e1284196") {
            return self::respond('Unauthorized.');
        }

        return self::respond("dick and balls");
    }

    /**
     * Responds with plain text
     *
     * @param string $data
     * @return Response
     */
    private static function respond(string $data): Response
    {
        return (new Response($data, 200))->header('Content-Type', 'text/plain');
    }
}
