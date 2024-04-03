<?php

namespace App\Http\Controllers;

use App\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    public function banExceptions(Request $request)
    {
        $exceptions = Player::query()
            ->select('license_identifier', 'player_name', DB::raw("JSON_EXTRACT(user_data, '$.twitchBanException') as twitchBanException"))
            ->whereNotNull(DB::raw("JSON_EXTRACT(user_data, '$.twitchBanException')"))
            ->get();

        $list = [];

        foreach ($exceptions as $exception) {
            $list[] = sprintf(
                '<a href="/players/%s" target="_blank">%s</a> - <a href="https://twitch.tv/%s" target="_blank">%s</a>',
                $exception->license_identifier,
                $exception->getSafePlayerName(),
                $exception->twitchBanException,
                $exception->twitchBanException
            );
        }

        // Sort list by player names
        usort($list, function ($a, $b) {
            return strcasecmp(strip_tags($a), strip_tags($b));
        });

        return $this->respond("Ban Exceptions", implode("\n", $list));
    }

    private function respond(string $title, string $data)
    {
        $data = sprintf('<title>%s</title><h3 style="margin:0 0 5px 0">%s</h3>%s', $title, $title, $data);

        return $this->fakeText(200, $data);
    }
}
