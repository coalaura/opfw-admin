<?php

namespace App\Http\Controllers;

use App\PanelLog;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
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

        // Filtering by source_identifier.
        $this->searchQuery($request, $query, 'source', 'source_identifier');

        // Filtering by target_identifier.
        $this->searchQuery($request, $query, 'target', 'target_identifier');

        // Filtering by action.
        $this->searchQuery($request, $query, 'action', 'action');

        // Filtering by log.
        $this->searchQuery($request, $query, 'log', 'log');

        $page = Paginator::resolveCurrentPage('page');

        $query->select(['id', 'source_identifier', 'target_identifier', 'timestamp', 'log', 'action']);
        $query->limit(15)->offset(($page - 1) * 15);

        $logs = $query->get()->toArray();

        $sources = PanelLog::query()
            ->select(['source_identifier'])
            ->groupBy('source_identifier')
            ->get()->toArray();

        $end = round(microtime(true) * 1000);

        $identifiers = $sources;
        foreach ($logs as $log) {
            $license = $log['target_identifier'];

            $identifiers[] = [
                'source_identifier' => $license,
            ];
        }

        return Inertia::render('PanelLogs/Index', [
            'logs'      => $logs,
            'sources'   => $sources,
            'filters'   => [
                'source' => $request->input('source') ?? '',
                'target' => $request->input('target'),
                'action' => $request->input('action'),
                'log'    => $request->input('log'),
            ],
            'links'     => $this->getPageUrls($page),
            'time'      => $end - $start,
            'playerMap' => Player::fetchLicensePlayerNameMap($identifiers, ['source_identifier']),
            'page'      => $page,
        ]);
    }

}
