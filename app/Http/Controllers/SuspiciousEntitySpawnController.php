<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Player;
use App\SuspiciousEntitySpawn;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuspiciousEntitySpawnController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_SUSPICIOUS_ENTITIES)) {
            abort(401);
        }

        $query = SuspiciousEntitySpawn::query()->orderBy('timestamp', 'desc');

        if ($request->filled('license')) {
            $query->where('license_identifier', $request->input('license'));
        }

        if ($request->filled('before')) {
            $query->where('timestamp', '<', $request->input('before'));
        }

        if ($request->filled('after')) {
            $query->where('timestamp', '>', $request->input('after'));
        }

        if ($request->filled('types') && is_array($request->input('types')) && count($request->input('types')) > 0) {
            $query->whereIn('type', $request->input('types'));
        }

        $page = $query->paginate(15);
        $logs = $page->items();

        $map = Player::fetchLicensePlayerNameMap($logs, 'license_identifier');

        return Inertia::render('Suspicious/EntitySpawns', [
            'logs'      => $logs,
            'filters'   => [
                'license' => $request->input('license') ?? '',
                'before'  => $request->input('before') ?? '',
                'after'   => $request->input('after') ?? '',
                'types'   => $request->input('types') ?? [],
            ],
            'links'     => $this->getPageUrls($page->currentPage()),
            'page'      => $page->currentPage(),
            'total'     => $page->total(),
            'playerMap' => $map,
        ]);
    }

}
