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
        if (!$this->isRoot($request)) {
            abort(401);
        }

        $start = microtime(true);
        DB::select(DB::raw("SELECT 1"));
        $selectTime = round((microtime(true) - $start) * 1000);

        $server     = Server::getFirstServer();
        $serverTime = null;

        if ($server) {
            $start = microtime(true);
            GeneralHelper::get($server . 'api.json');
            $serverTime = round((microtime(true) - $start) * 1000);
        }

        $data = [
            'ip'             => $request->ip(),
            'userAgent'      => $request->userAgent(),
            'fingerprint'    => $request->fingerprint(),

            'SELECT 1'       => $this->formatMilliseconds($selectTime),
            '/api.json'      => $this->formatMilliseconds($serverTime),

            'accept'         => $request->header('accept'),
            'acceptLanguage' => $request->header('accept-language'),
            'acceptEncoding' => $request->header('accept-encoding'),
        ];

        return $this->json(true, $data);
    }
}
