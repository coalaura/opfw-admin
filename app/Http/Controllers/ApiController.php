<?php

namespace App\Http\Controllers;

use App\Helpers\GeneralHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Server;
use App\Player;
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
        $start      = microtime(true);
        $one        = DB::select(DB::raw("SELECT 1 as one"));
        $selectTime = GeneralHelper::formatMilliseconds(round((microtime(true) - $start) * 1000));

        if (!$one || $one[0]->one !== 1) {
            $selectTime = false;
        }

        // Server API test
        $start      = microtime(true);
        $api        = OPFWHelper::getVariablesJSON(Server::getFirstServer());
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

            [], // separator

            ['request_ip', $request->ip()],
            ['user_agent', $request->userAgent()],
        ];

        return $this->json(true, [
            'time' => microtime(true) - $debugStart,
            'info' => $data,
        ]);
    }

    public function banExceptions(Request $request)
    {
        $exceptions = Player::query()
            ->select('license_identifier', 'player_name', DB::raw("JSON_EXTRACT(user_data, '$.twitchBanException') as twitchBanException"))
            ->whereNotNull(DB::raw("JSON_EXTRACT(user_data, '$.twitchBanException')"))
            ->get();

        $list = [];

        foreach ($exceptions as $exception) {
            $list[] = sprintf('<a href="" target="_blank"></a> - <a href="" target="_blank">%s</a>', $exception->getSafePlayerName(), $exception->twitchBanException);
        }

        $this->fakeText(200, implode("\n", $list));
    }
}
