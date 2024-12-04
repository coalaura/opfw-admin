<?php

namespace App\Http\Controllers;

use App\ClientError;
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
            abort(401);
        }

        $start = round(microtime(true) * 1000);

        $query = ClientError::query()
            ->leftJoin('users', 'users.license_identifier', '=', 'errors_client.license_identifier')
            ->selectRaw("error_id, errors_client.license_identifier, player_name, error_location, error_trace, full_trace, error_feedback, server_id, timestamp, server_version, COUNT(error_id) as `occurrences`")
            ->orderByDesc('timestamp')
            ->groupByRaw("error_location, error_trace, COALESCE(error_feedback, ''), FLOOR(timestamp / 300)");

        $page = Paginator::resolveCurrentPage('page');

        $query->limit(50)->offset(($page - 1) * 50);

        $errors = $query->get()->toArray();

        // "Repair" trace
        foreach ($errors as &$error) {
            $trace = $error['error_trace'];

            if (strpos($trace, "\n") !== false) {
                continue;
            }

            $error['error_trace'] = $trace . "\nstack traceback:\n" . implode("\n", json_decode($error['full_trace'], true));
        }

        $end = round(microtime(true) * 1000);

        return Inertia::render('Errors/Index', [
            'errors' => $errors,
            'links'  => $this->getPageUrls($page),
            'time'   => $end - $start,
            'page'   => $page,
            'type'   => 'client',
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
            abort(401);
        }

        $start = round(microtime(true) * 1000);

        $query = ServerError::query()
            ->selectRaw("error_id, error_location, error_trace, server_id, timestamp, server_version, COUNT(error_id) as `occurrences`")
            ->orderByDesc('timestamp')
            ->groupByRaw("error_location, error_trace, FLOOR(timestamp / 300)");

        $page = Paginator::resolveCurrentPage('page');

        $query->limit(50)->offset(($page - 1) * 50);

        $errors = $query->get()->toArray();

        // "Repair" trace
        foreach ($errors as &$error) {
            $trace = $error['error_trace'];

            if (strpos($trace, "(...tail calls...)") === false) {
                continue;
            }

            $split = preg_split('/\s+\(...tail calls...\)/', $trace);

            $error['error_trace'] = $split[0];
        }

        $end = round(microtime(true) * 1000);

        return Inertia::render('Errors/Index', [
            'errors' => $errors,
            'links'  => $this->getPageUrls($page),
            'time'   => $end - $start,
            'page'   => $page,
            'type'   => 'server',
        ]);
    }

}
