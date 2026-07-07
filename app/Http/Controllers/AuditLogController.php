<?php

namespace App\Http\Controllers;

use App\AuditLog;
use App\Helpers\PermissionHelper;
use App\Http\Resources\AuditLogResource;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{

    /**
     * Display a listing of the audit log.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_VIEW_AUDIT_LOGS)) {
            abort(401);
        }

        $start = round(microtime(true) * 1000);

        $query = AuditLog::query()->orderByDesc('timestamp');

        // Filtering by actor license.
        $this->searchQuery($request, $query, 'license', 'license');

        // Filtering by action.
        $this->searchQuery($request, $query, 'action', 'action');

        // Filtering by target type.
        $this->searchQuery($request, $query, 'target_type', 'target_type');

        // Filtering by target id.
        $this->searchQuery($request, $query, 'target_id', 'target_id');

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

        $query->select(['id', 'license', 'action', 'target_type', 'target_id', 'details', 'metadata', 'timestamp']);
        $query->limit(30)->offset(($page - 1) * 30);

        $logs = $query->get();

        $logs = AuditLogResource::collection($logs);

        $end = round(microtime(true) * 1000);

        return Inertia::render('Logs/AuditLogs', [
            'logs'           => $logs,
            'filters'        => $request->all(
                'license',
                'action',
                'target_type',
                'target_id',
                'details',
                'after',
                'before'
            ),
            'links'          => $this->getPageUrls($page),
            'time'           => $end - $start,
            'playerMap'      => Player::fetchLicensePlayerNameMap($logs->toArray($request), 'license'),
            'page'           => $page,
            'targetTypes'    => AuditLog::TargetTypes,
        ]);
    }
}
