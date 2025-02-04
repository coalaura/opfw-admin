<?php

namespace App\Http\Controllers;

use App\Http\Resources\PanelLogResource;
use App\PanelLog;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PanelLogController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $start = round(microtime(true) * 1000);

        $query = PanelLog::query()->orderByDesc('timestamp');

        // Filtering by identifier.
        $this->searchQuery($request, $query, 'identifier', 'identifier');

        // Filtering by action.
        $this->searchQuery($request, $query, 'action', 'action');

        // Filtering by details.
        $this->searchQuery($request, $query, 'details', 'details');

        // Filtering by before.
        if ($before = intval($request->input('before'))) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '<', $before);
        }

        // Filtering by after.
        if ($after = intval($request->input('after'))) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '>', $after);
        }

        $page = Paginator::resolveCurrentPage('page');

        $query->select(['id', 'identifier', 'action', 'details', 'metadata', 'timestamp']);
        $query->limit(30)->offset(($page - 1) * 30);

        $logs = $query->get();

        $logs = PanelLogResource::collection($logs);

        $end = round(microtime(true) * 1000);

        return Inertia::render('Logs/PanelLogs', [
            'logs'           => $logs,
            'filters'        => $request->all(
                'identifier',
                'action',
                'details',
                'after',
                'before'
            ),
            'links'          => $this->getPageUrls($page),
            'time'           => $end - $start,
            'playerMap'      => Player::fetchLicensePlayerNameMap($logs->toArray($request), 'licenseIdentifier'),
            'page'           => $page,
            'actions'        => PanelLog::Actions,
        ]);
    }
}
