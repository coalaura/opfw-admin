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
        return Inertia::render('Statistics/Index');
    }

    /**
     * Resolves a certain statistic.
     */
    public function source(string $source)
    {
        $result = false;

        switch ($source) {
            case 'pdm':
                $result = StatisticsHelper::collectPDMStatistics();
                break;
            case 'edm':
                $result = StatisticsHelper::collectEDMStatistics();
                break;
            case 'gem':
                $result = StatisticsHelper::collectGemSaleStatistics();
                break;
            case 'pawn':
                $result = StatisticsHelper::collectPawnshopStatistics();
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
        }

        return $this->json(true, $result);
    }

}
