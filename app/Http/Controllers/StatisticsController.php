<?php
namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\StatisticsHelper;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    private $colorHueStart;

    /**
     * Renders the home page.
     *
     * @param Request $request
     * @return Response
     */
    public function render(Request $request): Response
    {
        return Inertia::render('Statistics/Index');
    }

    /**
     * Resolves a certain statistic.
     */
    public function source(string $source)
    {
        $result = false;

        $key = "statistics.{$source}";

        if (CacheHelper::exists($key) && CLUSTER !== "c1") {
            $result = CacheHelper::read($key) ?? false;
        }

        if (! $result) {
            switch ($source) {
                // Currency statistics
                case 'pdm':
                    $result = StatisticsHelper::collectPDMStatistics();
                    break;
                case 'edm':
                    $result = StatisticsHelper::collectEDMStatistics();
                    break;
                case 'special_imports':
                    $result = StatisticsHelper::collectSpecialImportsStatistics();
                    break;
                case 'tuner':
                    $result = StatisticsHelper::collectTunerStatistics();
                    break;
                case 'gem':
                    $result = StatisticsHelper::collectGemSaleStatistics();
                    break;
                case 'pawn':
                    $result = StatisticsHelper::collectPawnshopStatistics();
                    break;
                case 'material_vendor':
                    $result = StatisticsHelper::collectMaterialVendorStatistics();
                    break;
                case 'casino':
                    $result = StatisticsHelper::collectCasinoStatistics();
                    break;
                case 'drugs':
                    $result = StatisticsHelper::collectDrugSaleStatistics();
                    break;
                case 'store':
                    $result = StatisticsHelper::collectStoreSaleStatistics();
                    break;
                case 'paycheck':
                    $result = StatisticsHelper::collectPaycheckStatistics();
                    break;
                case 'impounds':
                    $result = StatisticsHelper::collectImpoundsStatistics();
                    break;
                case 'robbed_peds':
                    $result = StatisticsHelper::collectRobbedPedsStatistics();
                    break;
                case 'bills':
                    $result = StatisticsHelper::collectBillsStatistics();
                    break;
                case 'scratch_tickets':
                    $result = StatisticsHelper::collectScratchTicketStatistics();
                    break;
                case 'daily_refresh':
                    $result = StatisticsHelper::collectDailyRefreshStatistics();
                    break;
                case 'atm_withdraw_fee':
                    $result = StatisticsHelper::collectATMWithdrawFeesStatistics();
                    break;
                case 'bus_revenue':
                    $result = StatisticsHelper::collectBusDriverStatistics();
                    break;
                case 'found_items':
                    $result = StatisticsHelper::collectFoundItemsStatistics();
                    break;
                case 'ls_customs':
                    $result = StatisticsHelper::collectLSCustomsStatistics();
                    break;

                // Non currency statistics
                case 'robberies':
                    $result = StatisticsHelper::collectRobberiesStatistics();
                    break;
                case 'joins':
                    $result = StatisticsHelper::collectJoinsStatistics();
                    break;
                case 'ooc':
                    $result = StatisticsHelper::collectOOCMessagesStatistics();
                    break;
                case 'reports':
                    $result = StatisticsHelper::collectReportsStatistics();
                    break;
                case 'daily_tasks':
                    $result = StatisticsHelper::collectDailyTasksStatistics();
                    break;
                case 'deaths':
                    $result = StatisticsHelper::collectDeathsStatistics();
                    break;
                case 'airlifts':
                    $result = StatisticsHelper::collectAirliftsStatistics();
                    break;
                case 'mining_explosions':
                    $result = StatisticsHelper::collectMiningExplosionStatistics();
                    break;
                case 'dumpsters':
                    $result = StatisticsHelper::collectDumpsterStatistics();
                    break;
                case 'guns':
                    $result = StatisticsHelper::collectGunCraftingStatistics();
                    break;
                case 'crashes_hourly':
                    $result = StatisticsHelper::collectGameCrashHourlyStatistics();
                    break;
                case 'crashes_daily':
                    $result = StatisticsHelper::collectGameCrashDailyStatistics();
                    break;
                case 'shots_fired':
                    $result = StatisticsHelper::collectShotsFiredStatistics();
                    break;
                case 'lucky_wheel':
                    $result = StatisticsHelper::collectLuckyWheelStatistics();
                    break;
                case 'blackjack':
                    $result = StatisticsHelper::collectBlackjackWinStatistics();
                    break;
                case 'found_items_count':
                    $result = StatisticsHelper::collectFoundItemsCountStatistics();
                    break;
            }

            CacheHelper::write($key, $result, CacheHelper::HOUR);
        }

        return $this->json(true, $result);
    }

    /**
     * Resolves all staffs staff points.
     */
    public function points(Request $request)
    {
        $staff = Player::query()
            ->where('is_staff', '=', '1')
            ->orWhere('is_senior_staff', '=', '1')
            ->orWhere('is_super_admin', '=', '1')
            ->orderBy('player_name')
            ->get();

        $points = [];

        $start = strtotime('monday this week');

        foreach ($staff as $player) {
            $license     = $player->license_identifier;
            $staffPoints = $player->staff_points ?? [];

            $points[$license] = [
                'name'   => $player->getSafePlayerName(),
                'points' => [],
            ];

            for ($week = 7; $week >= 0; $week--) {
                $time = $start - ($week * 604800);
                $date = sprintf('%s-%d', date('o', $time), intval(date('W', $time)));

                $points[$license]['points'][abs($week)] = ($staffPoints[$date] ?? 0);
            }
        }

        return Inertia::render('Statistics/StaffPoints', [
            'points' => $points,
        ]);
    }

    /**
     * Resolves all staffs statistics.
     */
    public function staffStatistics(Request $request)
    {
        $staff = Player::query()
            ->where('is_staff', '=', '1')
            ->orWhere('is_senior_staff', '=', '1')
            ->orWhere('is_super_admin', '=', '1')
            ->select('user_id', 'license_identifier', 'player_name')
            ->get();

        $keys     = [];
        $licenses = [];
        $players  = [];

        foreach ($staff as $player) {
            $license = $player->license_identifier;

            $licenses[]        = $license;
            $players[$license] = [
                'license' => $license,
                'name'    => $player->getSafePlayerName(),
                'xp'      => 0,
            ];
        }

        $query = DB::table('staff_statistics')
            ->whereIn('identifier', $licenses)
            ->selectRaw('identifier, action, count(*) as count')
            ->groupBy('action', 'identifier');

        $from     = $request->get('from');
        $fromTime = $from ? strtotime($from . ' 00:00:00') : false;

        if ($fromTime) {
            $query->where('timestamp', '>=', $fromTime);
        }

        $to     = $request->get('to');
        $toTime = $to ? strtotime($to . ' 23:59:59') : false;

        if ($toTime) {
            $query->where('timestamp', '<=', $toTime);
        }

        $statistics = $query->get();

        foreach ($statistics as $statistic) {
            $license = $statistic->identifier;
            $action  = $statistic->action;
            $count   = $statistic->count;

            if (! in_array($action, $keys)) {
                $keys[] = $action;
            }

            $players[$license][$action] = ($players[$license][$action] ?? 0) + $count;
        }

        foreach ($players as $license => $actions) {
            $players[$license]['xp'] = Player::calculateXp($actions);
        }

        usort($players, function ($a, $b) {
            if ($a['xp'] === $b['xp']) {
                return $a['name'] <=> $b['name'];
            }

            return $b['xp'] <=> $a['xp'];
        });

        sort($keys);

        return Inertia::render('Statistics/Staff', [
            'keys'    => $keys,
            'players' => $players,
            'filters' => [
                'from' => $from,
                'to'   => $to,
            ],
        ]);
    }

    public function economyStatistics()
    {
        $datasets = 9;

        $statistics = [
            "data"  => [],
            "graph" => [
                "datasets" => [
                    [
                        "label"           => "Cash",
                        "data"            => [],
                        "backgroundColor" => $this->color(0, $datasets, 0.3),
                        "borderColor"     => $this->color(0, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Bank",
                        "data"            => [],
                        "backgroundColor" => $this->color(1, $datasets, 0.3),
                        "borderColor"     => $this->color(1, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Stocks",
                        "data"            => [],
                        "backgroundColor" => $this->color(2, $datasets, 0.3),
                        "borderColor"     => $this->color(2, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Savings",
                        "data"            => [],
                        "backgroundColor" => $this->color(3, $datasets, 0.3),
                        "borderColor"     => $this->color(3, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Shared",
                        "data"            => [],
                        "backgroundColor" => $this->color(4, $datasets, 0.3),
                        "borderColor"     => $this->color(4, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Bonds",
                        "data"            => [],
                        "backgroundColor" => $this->color(5, $datasets, 0.3),
                        "borderColor"     => $this->color(5, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Richest",
                        "data"            => [],
                        "backgroundColor" => $this->color(6, $datasets, 0.3),
                        "borderColor"     => $this->color(6, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Poorest",
                        "data"            => [],
                        "backgroundColor" => $this->color(7, $datasets, 0.3),
                        "borderColor"     => $this->color(7, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Total",
                        "data"            => [],
                        "backgroundColor" => $this->color(8, $datasets, 0.3),
                        "borderColor"     => $this->color(8, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                ],
                "labels"   => [],
            ],
        ];

        $data = StatisticsHelper::collectEconomyStatistics();

        $min = strtotime("-30 days");

        foreach ($data as $entry) {
            $date = $entry->date;

            $time = strtotime($date);

            $total = $entry->cash + $entry->bank + $entry->stocks + $entry->savings + $entry->shared + $entry->bonds;

            if ($time >= $min) {
                $statistics["data"][$date] = [
                    "date"    => $date,
                    "cash"    => $entry->cash,
                    "bank"    => $entry->bank,
                    "stocks"  => $entry->stocks,
                    "savings" => $entry->savings,
                    "shared"  => $entry->shared,
                    "bonds"   => $entry->bonds,
                    "richest" => $entry->richest,
                    "poorest" => $entry->poorest,
                    "total"   => $total,
                ];
            }

            $statistics["graph"]["labels"][] = $date;

            $statistics["graph"]["datasets"][0]["data"][] = $entry->cash;
            $statistics["graph"]["datasets"][1]["data"][] = $entry->bank;
            $statistics["graph"]["datasets"][2]["data"][] = $entry->stocks;
            $statistics["graph"]["datasets"][3]["data"][] = $entry->savings;
            $statistics["graph"]["datasets"][4]["data"][] = $entry->shared;
            $statistics["graph"]["datasets"][5]["data"][] = $entry->bonds;
            $statistics["graph"]["datasets"][6]["data"][] = $entry->richest;
            $statistics["graph"]["datasets"][7]["data"][] = $entry->poorest;
            $statistics["graph"]["datasets"][8]["data"][] = $total;
        }

        $statistics["data"] = array_reverse(array_values($statistics["data"]));

        return $this->json(true, $statistics);
    }

    public function playerStatistics()
    {
        $datasets = 4;

        $statistics = [
            "data"  => [],
            "graph" => [
                "datasets" => [
                    [
                        "label"           => "Total Joins",
                        "data"            => [],
                        "backgroundColor" => $this->color(0, $datasets, 0.3),
                        "borderColor"     => $this->color(0, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Max Users",
                        "data"            => [],
                        "backgroundColor" => $this->color(1, $datasets, 0.3),
                        "borderColor"     => $this->color(1, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Max Queue",
                        "data"            => [],
                        "backgroundColor" => $this->color(2, $datasets, 0.3),
                        "borderColor"     => $this->color(2, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Unique Users",
                        "data"            => [],
                        "backgroundColor" => $this->color(3, $datasets, 0.3),
                        "borderColor"     => $this->color(3, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                ],
                "labels"   => [],
            ],
        ];

        $data = StatisticsHelper::collectUserStatistics();

        $min = strtotime("-30 days");

        foreach ($data as $entry) {
            $date = $entry->date;

            $time = strtotime($entry->date);

            if ($time >= $min) {
                $statistics["data"][$date] = [
                    "date"        => $date,
                    "total_joins" => $entry->total_joins,
                    "max_users"   => $entry->max_joined,
                    "max_queue"   => $entry->max_queue,
                    "unique"      => $entry->joined_users,
                ];
            }

            $statistics["graph"]["labels"][] = $date;

            $statistics["graph"]["datasets"][0]["data"][] = $entry->total_joins;
            $statistics["graph"]["datasets"][1]["data"][] = $entry->max_joined;
            $statistics["graph"]["datasets"][2]["data"][] = $entry->max_queue;
            $statistics["graph"]["datasets"][3]["data"][] = $entry->joined_users;
        }

        $statistics["data"] = array_reverse(array_values($statistics["data"]));

        return $this->json(true, $statistics);
    }

    public function fpsStatistics()
    {
        $datasets = 5;

        $statistics = [
            "data"  => [],
            "graph" => [
                "datasets" => [
                    [
                        "label"           => "Min Average FPS",
                        "data"            => [],
                        "backgroundColor" => $this->color(0, $datasets, 0.3),
                        "borderColor"     => $this->color(0, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Max Average FPS",
                        "data"            => [],
                        "backgroundColor" => $this->color(1, $datasets, 0.3),
                        "borderColor"     => $this->color(1, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Average FPS",
                        "data"            => [],
                        "backgroundColor" => $this->color(2, $datasets, 0.3),
                        "borderColor"     => $this->color(2, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Average 1% FPS",
                        "data"            => [],
                        "backgroundColor" => $this->color(3, $datasets, 0.3),
                        "borderColor"     => $this->color(3, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                    [
                        "label"           => "Average Lag Spikes",
                        "data"            => [],
                        "backgroundColor" => $this->color(4, $datasets, 0.3),
                        "borderColor"     => $this->color(4, $datasets, 1),
                        "pointRadius"     => 0,
                    ],
                ],
                "labels"   => [],
            ],
        ];

        $data = StatisticsHelper::collectFPSStatistics();

        $min = strtotime("-30 days");

        foreach ($data as $entry) {
            $date = $entry->date;

            $time = strtotime($entry->date);

            if ($time >= $min) {
                $statistics["data"][$date] = [
                    "date"              => $date,
                    "minimum"           => $entry->minimum,
                    "maximum"           => $entry->maximum,
                    "average"           => $entry->average,
                    "average_1_percent" => $entry->average_1_percent,
                    "lag_spikes"        => $entry->lag_spikes,
                ];
            }

            $statistics["graph"]["labels"][] = $date;

            $statistics["graph"]["datasets"][0]["data"][] = $entry->minimum;
            $statistics["graph"]["datasets"][1]["data"][] = $entry->maximum;
            $statistics["graph"]["datasets"][2]["data"][] = $entry->average;
            $statistics["graph"]["datasets"][3]["data"][] = $entry->average_1_percent;
            $statistics["graph"]["datasets"][4]["data"][] = $entry->lag_spikes;
        }

        $statistics["data"] = array_reverse(array_values($statistics["data"]));

        return $this->json(true, $statistics);
    }

    public function moneyLogs(Request $request)
    {
        $types = $request->input('types', []);

        if (! is_array($types) || empty($types)) {
            return $this->json(false, null, 'Invalid types');
        }

        $types = array_values(array_filter(array_map(function ($type) {
            return preg_replace('/[^\w-]/', '', $type);
        }, $types)));

        $data = StatisticsHelper::collectSpecificMoneyStatistics($types);

        if (empty($data)) {
            return $this->json(false, null, 'No data found');
        }

        // Map the data
        $map = [];

        foreach ($data as $value) {
            $date = strtotime($value->date);

            $details = $value->details;
            $amount  = $value->amount;

            if (! isset($map[$date])) {
                $map[$date] = [];
            }

            if (! isset($map[$date][$details])) {
                $map[$date][$details] = $amount;
            }
        }

        $times = array_keys($map);
        $min   = min($times);
        $max   = max($times);

        rsort($times);

        $chart = [
            'datasets' => [],
            'labels'   => [],
            'names'    => $types,
        ];

        for ($t = $min; $t <= $max; $t += 86400) {
            $date = date('Y-m-d', $t);

            $chart['labels'][] = $date;

            $entry = $map[$t] ?? [];

            foreach ($types as $i => $type) {
                if (sizeof($chart['datasets']) <= $i) {
                    $chart['datasets'][$i] = [
                        'label'           => $type,
                        'data'            => [],
                        'backgroundColor' => $this->color($i, sizeof($types), 0.3),
                        'borderColor'     => $this->color($i, sizeof($types), 1),
                    ];
                }

                $chart['datasets'][$i]['data'][] = $entry[$type] ?? 0;
            }
        }

        return $this->json(true, [
            'chart' => $chart,
            'types' => $types,
        ]);
    }

    private function color($index, $total, $alpha): string
    {
        if (! isset($this->colorHueStart)) {
            $this->colorHueStart = rand(0, 360);
        }

        $step = 360 / ($total + 1);

        $hue = ($this->colorHueStart + ($index * $step)) % 360;

        return "hsla({$hue}, 70%, 65%, {$alpha})";
    }
}
