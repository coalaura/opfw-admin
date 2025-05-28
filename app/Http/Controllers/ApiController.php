<?php
namespace App\Http\Controllers;

use App\Character;
use App\Helpers\GeneralHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function crafting(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_CRAFTING)) {
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

        if (! $this->isRoot($request)) {
            abort(401);
        }

        // Database connection test
        $start      = microtime(true);
        $one        = DB::select(DB::raw("SELECT 1 as one"));
        $selectTime = GeneralHelper::formatMilliseconds(round((microtime(true) - $start) * 1000));

        if (! $one || $one[0]->one !== 1) {
            $selectTime = false;
        }

        // Server API test
        $start      = microtime(true);
        $api        = ServerAPI::getVariables();
        $serverTime = GeneralHelper::formatMilliseconds(round((microtime(true) - $start) * 1000));

        if (! $api) {
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
        $data = ServerAPI::getConfigFresh();

        if (! $data) {
            abort(404);
        }

        $setting = $data[$key] ?? false;

        if (! $setting) {
            return $this->json(false);
        }

        return $this->json(true, [
            'type'  => $setting['type'] ?: '',
            'value' => $setting['value'] ?: '',
        ]);
    }

    public function token()
    {
        return $this->json(true, [
            "token"   => session_token(),
            "expires" => time() + (4 * 60 * 60),
        ]);
    }

    public function painting()
    {
        $painting = DB::table('inventories')
            ->where('item_name', '=', 'picture')
            ->whereRaw("JSON_EXTRACT(item_metadata, '$.artistId') IS NOT NULL")
            ->inRandomOrder()
            ->first();

        if (! $painting) {
            return $this->json(false, null, "no painting found");
        }

        $metadata = json_decode($painting->item_metadata);

        $artist = Character::query()
            ->where('character_id', '=', $metadata->artistId)
            ->first();

        return $this->json(true, [
            'source'    => $metadata->pictureURL,
            'inventory' => $painting->inventory_name,
            'artist'    => $artist ? [
                'id'      => $artist->character_id,
                'name'    => sprintf('%s %s', $artist->first_name, $artist->last_name),
                'license' => $artist->license_identifier,
            ] : null,
        ]);
    }
}
