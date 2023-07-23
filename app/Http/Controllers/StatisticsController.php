<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
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
        $license = $request->user()->player->license_identifier;

        return Inertia::render('Statistics/Index', [
            'bans'       => StatisticsHelper::getBanStats(),
            'warnings'   => StatisticsHelper::getWarningStats(),
            'notes'      => StatisticsHelper::getNoteStats(),
            'creations'  => StatisticsHelper::getCharacterCreationStats(),
            'deletions'  => StatisticsHelper::getCharacterDeletionStats(),
            'userStatistics'  => StatisticsHelper::getUserStatistics(),
            'commandStatistics'  => StatisticsHelper::getCommandStatistics(),
            'luckyWheel' => StatisticsHelper::getLuckyWheelStats(),
            'blackjack'  => StatisticsHelper::getBlackjackStats($license),
            'tracks'     => StatisticsHelper::getTracksStats($license),
            'slots'      => StatisticsHelper::getSlotsStats($license),

            'casinoRevenue' => StatisticsHelper::getCasinoRevenueStats(),
        ]);
    }

}
