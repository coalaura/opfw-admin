<?php

namespace App\Http\Controllers;

use App\Character;
use App\Helpers\GeneralHelper;
use App\Helpers\HttpHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class ApiController extends Controller
{
    public function crafting(Request $request): Response
    {
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_CRAFTING)) {
            abort(401);
        }

        $data = ServerAPI::getCrafting();

        return (new Response($data, 200))
            ->header('Content-Type', 'text/plain');
    }

    public function character(Character $character): Response
    {
        return $this->json(true, [
            'id'         => $character->character_id,
            'license'    => $character->license_identifier,
            'first_name' => $character->first_name,
            'last_name'  => $character->last_name,
        ]);
    }

    public function debug(Request $request): Response
    {
        $debugStart = microtime(true);

        if (!$this->isRoot($request)) {
            abort(401);
        }

        // Database connection test
        $start      = microtime(true);
        $one        = DB::select(DB::raw("SELECT 1 as one"));
        $selectTime = GeneralHelper::formatMilliseconds(round((microtime(true) - $start) * 1000));

        if (!$one || $one[0]->one !== 1) {
            $selectTime = false;
        }

        // Server API test
        $start      = microtime(true);
        $api        = ServerAPI::getVariables();
        $serverTime = GeneralHelper::formatMilliseconds(round((microtime(true) - $start) * 1000));

        if (!$api) {
            $serverTime = false;
        }

        $data = [
            ['system', php_uname()],
            ['system_uptime', GeneralHelper::getLastSystemRestartTime()],
            ['php_version', phpversion()],

            [], // separator

            ['database_check', $selectTime],
            ['api_variables', $serverTime],
            ['server_version', $api ? $api['serverVersion'] : '-'],
            ['server_host', $api ? $api['serverHost'] : '-'],

            [], // separator

            ['request_ip', $request->ip()],
            ['user_agent', $request->userAgent()],
        ];

        return $this->json(true, [
            'time' => microtime(true) - $debugStart,
            'info' => $data,
        ]);
    }

    public function config(string $key)
    {
        $data = ServerAPI::getConfig();

        if (!$data) {
            abort(404);
        }

        $setting = $data[$key] ?? false;

        return $this->json(true, $setting);
    }

    public function chatToken()
    {
        return $this->json(true, session_token());
    }
}
