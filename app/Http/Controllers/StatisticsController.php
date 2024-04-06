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

                // Other statistics
                case 'economy':
                    $result = StatisticsHelper::collectGenericEconomyStatistics();
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
            $license = $player->license_identifier;
            $staffPoints = $player->staff_points ?? [];

            $points[$license] = [
                'name' => $player->getSafePlayerName(),
                'points' => []
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

}
