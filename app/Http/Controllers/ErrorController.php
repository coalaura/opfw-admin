<?php

namespace App\Http\Controllers;

use App\ClientError;
use App\Player;
use App\ServerError;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Inertia\Inertia;
use Inertia\Response;

class ErrorController extends Controller
{

    /**
     * Renders the client errors page.
     *
     * @param Request $request
     * @return Response
     */
    public function client(Request $request): Response
    {
        if (!$this->isSuperAdmin($request)) {
            abort(403);
        }

        $start = round(microtime(true) * 1000);

        $versions = ClientError::query()
            ->selectRaw('server_version, MIN(timestamp) as timestamp')
            ->where('server_version', '!=', '')
            ->orderBy('timestamp', 'desc')
            ->groupBy('server_version')
            ->get()->toArray();

        $newestVersion = !empty($versions) ? $versions[0] : null;

        $query = ClientError::query()->orderByDesc('timestamp');

        // Filtering by error_trace.
        $this->searchQuery($request, $query, 'trace', 'error_trace');

        $serverVersion = $request->input('server_version');

        if ($serverVersion && $newestVersion) {
            if ($serverVersion === 'newest') {
                $serverVersion = $newestVersion['server_version'];
            }

            $query->where('server_version', '=', $serverVersion);
        }

        $page = Paginator::resolveCurrentPage('page');

        $query->groupByRaw("CONCAT(error_location, error_trace, IF(error_feedback IS NULL, '', error_feedback), FLOOR(timestamp / 300))");

        $query->selectRaw('error_id, license_identifier, error_location, error_trace, error_feedback, full_trace, player_ping, server_id, timestamp, server_version, COUNT(error_id) as `occurrences`');
        $query->orderBy('timestamp', 'desc');
        $query->limit(15)->offset(($page - 1) * 15);

        $errors = $query->get()->toArray();

        $end = round(microtime(true) * 1000);

        return Inertia::render('Errors/Client', [
            'errors'    => $errors,
            'versions'  => $versions,
            'filters'   => [
                'trace'          => $request->input('trace'),
                'server_version' => $serverVersion ?? null,
            ],
            'links'     => $this->getPageUrls($page),
            'playerMap' => Player::fetchLicensePlayerNameMap($errors, 'license_identifier'),
            'time'      => $end - $start,
            'page'      => $page,
        ]);
    }

    /**
     * Renders the server errors page.
     *
     * @param Request $request
     * @return Response
     */
    public function server(Request $request): Response
    {
        if (!$this->isSuperAdmin($request)) {
            abort(403);
        }

        $start = round(microtime(true) * 1000);

        $versions = ServerError::query()
            ->selectRaw('server_version, MIN(timestamp) as timestamp')
            ->where('server_version', '!=', '')
            ->orderBy('timestamp', 'desc')
            ->groupBy('server_version')
            ->get()->toArray();

        $newestVersion = !empty($versions) ? $versions[0] : null;

        $query = ServerError::query()->orderByDesc('timestamp');

        // Filtering by error_trace.
        $this->searchQuery($request, $query, 'trace', 'error_trace');

        $serverVersion = $request->input('server_version');

        if ($serverVersion && $newestVersion) {
            if ($serverVersion === 'newest') {
                $serverVersion = $newestVersion['server_version'];
            }

            $query->where('server_version', '=', $serverVersion);
        }

        $page = Paginator::resolveCurrentPage('page');

        $query->groupByRaw("CONCAT(error_location, error_trace, FLOOR(timestamp / 300))");

        $query->selectRaw('error_id, error_location, error_trace, server_id, timestamp, server_version, COUNT(error_id) as `occurrences`');
        $query->orderBy('timestamp', 'desc');
        $query->limit(15)->offset(($page - 1) * 15);

        $errors = $query->get()->toArray();

        $end = round(microtime(true) * 1000);

        return Inertia::render('Errors/Server', [
            'errors'   => $errors,
            'versions' => $versions,
            'filters'  => [
                'trace'          => $request->input('trace'),
                'server_version' => $serverVersion ?? null,
            ],
            'links'    => $this->getPageUrls($page),
            'time'     => $end - $start,
            'page'     => $page,
        ]);
    }

}
