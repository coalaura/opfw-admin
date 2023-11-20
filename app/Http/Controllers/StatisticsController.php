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
        return Inertia::render('Statistics/Index', [
            'pdm'    => StatisticsHelper::collectPDMStatistics(),
            'edm'    => StatisticsHelper::collectEDMStatistics(),
            'gem'    => StatisticsHelper::collectGemSaleStatistics(),
            'pawn'   => StatisticsHelper::collectPawnshopStatistics(),
            'casino' => StatisticsHelper::collectCasinoStatistics(),
            'drugs'  => StatisticsHelper::collectDrugSaleStatistics(),
            'store'  => StatisticsHelper::collectStoreSaleStatistics(),
        ]);
    }

}
