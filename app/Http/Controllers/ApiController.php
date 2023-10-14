<?php

namespace App\Http\Controllers;

use App\Helpers\GeneralHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function crafting(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_CRAFTING)) {
            abort(401);
        }

        $data = OPFWHelper::getCraftingTxt(Server::getFirstServer() ?? '');

        return (new Response($data, 200))
            ->header('Content-Type', 'text/plain');
    }

    public function debug(Request $request): Response
    {
        $debugStart = microtime(true);

        if (!$this->isRoot($request)) {
            abort(401);
        }

        // Database connection test
        $start = microtime(true);
        $one = DB::select(DB::raw("SELECT 1 as one"));
        $selectTime = $this->formatMilliseconds(round((microtime(true) - $start) * 1000));

        if (!$one || $one[0]->one !== 1) {
            $selectTime = "unavailable";
        }

        // Server API test
        $start = microtime(true);
        $api = OPFWHelper::getApiJSON(Server::getFirstServer());
        $serverTime = $this->formatMilliseconds(round((microtime(true) - $start) * 1000));

        if (!$api) {
            $serverTime = "unavailable";
        }

        $data = [
            'ip'             => $request->ip(),
            'userAgent'      => $request->userAgent(),
            'fingerprint'    => $request->fingerprint(),

            'SELECT 1'       => $selectTime,
            '/api.json'      => $serverTime,

            'accept'         => $request->header('accept'),
            'acceptLanguage' => $request->header('accept-language'),
            'acceptEncoding' => $request->header('accept-encoding'),
        ];

        return $this->json(true, [
            'time' => microtime(true) - $debugStart,
            'info' => $data
        ]);
    }
}
