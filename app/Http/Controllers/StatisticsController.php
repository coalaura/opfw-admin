<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\StatisticsHelper;
use App\Player;
use Illuminate\Http\Request;
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

        $key = "statistics_i3.{$source}";

        if (CacheHelper::exists($key)) {
            $result = CacheHelper::read($key) ?? false;
        }

        if (!$result) {
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
                case 'shots_fired':
                    $result = StatisticsHelper::collectShotsFiredStatistics();
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

        foreach ($staff as $player) {
            $license     = $player->license_identifier;
            $staffPoints = $player->staff_points ?? [];

            $points[$license] = [
                'name'   => $player->getSafePlayerName(),
                'points' => [],
            ];

            for ($week = 7; $week >= 0; $week--) {
                $time = $week === 0 ? time() : strtotime("{$week} weeks ago");

                $date = date('Y', $time) . '-' . intval(date('W', $time));

                $points[$license]['points'][abs($week)] = $staffPoints[$date] ?? 0;
            }
        }

        return Inertia::render('Statistics/StaffPoints', [
            'points' => $points,
        ]);
    }

    public function economyStatistics()
    {
        $hours = 30 * 24;

        $statistics = [
            "data"  => [],
            "graph" => [
                "datasets" => [
                    [
                        "label"           => "Cash",
                        "data"            => [],
                        "backgroundColor" => $this->color(0, 5, 0.3),
                        "borderColor"     => $this->color(0, 5, 1),
                    ],
                    [
                        "label"           => "Bank",
                        "data"            => [],
                        "backgroundColor" => $this->color(1, 5, 0.3),
                        "borderColor"     => $this->color(1, 5, 1),
                    ],
                    [
                        "label"           => "Stocks",
                        "data"            => [],
                        "backgroundColor" => $this->color(2, 5, 0.3),
                        "borderColor"     => $this->color(2, 5, 1),
                    ],
                    [
                        "label"           => "Savings",
                        "data"            => [],
                        "backgroundColor" => $this->color(3, 5, 0.3),
                        "borderColor"     => $this->color(3, 5, 1),
                    ],
                    [
                        "label"           => "Total",
                        "data"            => [],
                        "backgroundColor" => $this->color(4, 5, 0.3),
                        "borderColor"     => $this->color(4, 5, 1),
                    ],
                ],
                "labels"   => [],
            ],
        ];

        $data = StatisticsHelper::collectEconomyStatistics($hours);

        foreach ($data as $entry) {
            $date = $entry->date;

            $total = $entry->cash + $entry->bank + $entry->stocks + $entry->savings;

            $statistics["data"][$date] = [
                "date"    => $date,
                "cash"    => $entry->cash,
                "bank"    => $entry->bank,
                "stocks"  => $entry->stocks,
                "savings" => $entry->savings,
                "total"   => $total,
            ];

            $statistics["graph"]["labels"][] = $date;

            $statistics["graph"]["datasets"][0]["data"][] = $entry->cash;
            $statistics["graph"]["datasets"][1]["data"][] = $entry->bank;
            $statistics["graph"]["datasets"][2]["data"][] = $entry->stocks;
            $statistics["graph"]["datasets"][3]["data"][] = $entry->savings;
            $statistics["graph"]["datasets"][4]["data"][] = $total;
        }

        $statistics["data"] = array_values($statistics["data"]);

        return $this->json(true, $statistics);
    }

    public function moneyLogs(Request $request)
    {
        $types = $request->input('types', []);

        if (!is_array($types) || empty($types)) {
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
            $count   = $value->count;
            $amount  = $value->amount;

            if (!isset($map[$date])) {
                $map[$date] = [];
            }

            if (!isset($map[$date][$details])) {
                $map[$date][$details] = round($amount / $count);
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
        if (!isset($this->colorHueStart)) {
            $this->colorHueStart = rand(0, 360);
        }

        $step = 360 / ($total + 1);

        $hue = ($this->colorHueStart + ($index * $step)) % 360;

        return "hsla({$hue}, 70%, 65%, {$alpha})";
    }
}
