<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Helpers\SuspiciousChecker;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Inertia\Inertia;
use Inertia\Response;

class SuspiciousController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_SUSPICIOUS)) {
            abort(401);
        }

        $start = round(microtime(true) * 1000);

        $logs = [];
        $map = 'identifier';

        // Filtering by type.
        if ($type = $request->input('logType')) {
            switch ($type) {
                case 'characters':
                    $logs = SuspiciousChecker::findSuspiciousCharacters();
                    $map = 'license_identifier';
                    break;
                case 'pawn':
                    $logs = SuspiciousChecker::findSuspiciousPawnShopUsages();
                    break;
                case 'warehouse':
                    $logs = SuspiciousChecker::findSuspiciousWarehouseUsages();
                    break;
                case 'unusual':
                    $logs = SuspiciousChecker::findUnusualItems();
                    break;
            }
        }

        $type = $type ?? '';

        $page = Paginator::resolveCurrentPage('page');

        $total = sizeof($logs);

        $logs = array_slice($logs, ($page - 1) * 15, 15);

        $map = $type == 'inventories' || $type == 'items' ? ['none' => 'none'] : Player::fetchLicensePlayerNameMap($logs, $map);

        $end = round(microtime(true) * 1000);

        $export = $request->get('export');
        if ($export && $export !== "some_random_token" && $export === env('DEV_API_KEY', '')) {
            return (new \Illuminate\Http\Response(json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200))->header('Content-Type', 'application/json');
        }

        return Inertia::render('Suspicious/Index', [
            'logs'      => $logs,
            'filters'   => [
                'logType' => $type,
            ],
            'links'     => $this->getPageUrls($page),
            'time'      => $end - $start,
            'page'      => $page,
            'total'     => $total,
            'logType'   => $type,
            'playerMap' => $map,
        ]);
    }

}
