<?php

namespace App\Http\Controllers;

use App\Helpers\OPFWHelper;
use App\Server;
use App\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\PermissionHelper;

class QueueController extends Controller
{
    /**
     * Renders the queue page.
     *
     * @param Request $request
     * @param string $server
     * @return Response
     */
    public function render(Request $request, string $server): Response
    {
		if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_VIEW_QUEUE)) {
            abort(401);
        }

        if (!Server::getServerApiURLFromName($server)) {
            abort(404, 'Unknown server.');
        }

        return Inertia::render('Queue/Index', [
            'server' => $server,
        ]);
    }

    /**
     * Queue api
     *
     * @param Request $request
     * @param string $server
     * @return \Illuminate\Http\Response
     */
    public function api(Request $request, string $server): \Illuminate\Http\Response
    {
		if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_VIEW_QUEUE)) {
            abort(401);
        }

        $serverIp = Server::getServerApiURLFromName($server);
        if (!$serverIp) {
            return self::json(false, null, 'Unknown server.');
        }

        $queue = OPFWHelper::getQueueJSON($serverIp) ?? [];

        return self::json(true, [
            'queue'     => $queue,
            'playerMap' => Player::fetchLicensePlayerNameMap($queue, ['licenseIdentifier']),
        ]);
    }

}
