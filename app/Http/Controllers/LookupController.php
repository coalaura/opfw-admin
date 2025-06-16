<?php

namespace App\Http\Controllers;

use App\Helpers\DiscordHelper;
use App\Helpers\GeneralHelper;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use SteamID;

class LookupController extends Controller
{

    /**
     * Renders the steam lookup page.
     *
     * @param Request $request
     * @return Response
     */
    public function renderSteam(Request $request): Response
    {
        return Inertia::render('Lookup/Steam');
    }

    /**
     * Returns player info from steam.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function playerInfoSteam(Request $request): \Illuminate\Http\Response
    {
        $search = trim($request->input('search'));

        $error   = false;
        $steamId = false;

        if (!empty($search)) {
            if (Str::startsWith($search, "https://steamcommunity.com/id/")) {
                $html = GeneralHelper::get($search);

                if ($html) {
                    $re = '/<script type="text\/javascript">\s+g_rgProfileData = ({.+?});/m';

                    preg_match($re, $html, $matches);

                    if (sizeof($matches) > 1) {
                        $profile = json_decode($matches[1]);

                        if (isset($profile->steamid)) {
                            $steamId = $profile->steamid;
                        } else {
                            $error = "Unable to get steam id from profile.";
                        }
                    } else {
                        $error = "Steam returned an invalid response.";
                    }
                } else {
                    $error = "Unable to get steam profile.";
                }
            } else if (Str::startsWith($search, "https://steamcommunity.com/profiles/")) {
                $re = '/https:\/\/steamcommunity\.com\/profiles\/(\d+)/m';

                preg_match($re, $search, $matches);

                if (sizeof($matches) > 1) {
                    $steamId = intval($matches[1]);
                } else {
                    $error = "Invalid steam profile url.";
                }
            } else {
                try {
                    $id = new SteamID($search);

                    $steamId = $id->ConvertToUInt64();
                } catch (\Exception $ex) {
                    $error = "Invalid search value.";
                }
            }
        } else {
            $error = "Please enter a steam id or profile url.";
        }

        $data = [
            'error' => $error,
        ];

        if ($steamId) {
            $data['steamId'] = $steamId;

            try {
                $id = new SteamID($steamId);

                $data['steamHex'] = "steam:" . dechex($id->ConvertToUInt64());

                $data['steam2'] = $id->RenderSteam2();
                $data['steam3'] = $id->RenderSteam3();

                $data['invite'] = 'http://s.team/p/' . $id->RenderSteamInvite();
            } catch (\Exception $ex) {
                $data['error'] = "Invalid steam id.";
            }
        }

        return (new \Illuminate\Http\Response(json_encode($data), 200))
            ->header('Content-Type', 'application/json');
    }

    /**
     * Renders the discord lookup page.
     *
     * @param Request $request
     * @return Response
     */
    public function renderDiscord(Request $request): Response
    {
        return Inertia::render('Lookup/Discord');
    }

    /**
     * Returns player info from discord.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function playerInfoDiscord(Request $request): \Illuminate\Http\Response
    {
        $search = trim($request->input('search'));

        $error = false;
        $user  = false;

        if (!empty($search)) {
            if (preg_match('/^\d{17,19}$/mi', $search)) {
                $discordId = intval($search);

                $min = 1420070400000; // 2015-01-01 00:00:00
                $max = time() * 1000;

                $timestamp = ($discordId >> 22) + 1420070400000;

                if ($timestamp < $min || $timestamp > $max) {
                    $discordId = false;
                    $error     = "Invalid discord id.";
                } else {
                    try {
                        $user = DiscordHelper::getUserInfo($discordId);
                    } catch (\Exception $ex) {
                        $error = $ex->getMessage();
                    }
                }
            } else {
                try {
                    $user = DiscordHelper::findUserByName($search);

                    if ($user) {
                        $user = $user['user'];
                    } else {
                        $user  = false;
                        $error = "No user found with that name.";
                    }
                } catch (\Exception $ex) {
                    $error = $ex->getMessage();
                }
            }
        } else {
            $error = "Please enter a discord id or username.";
        }

        $data = [
            'error' => $error,
        ];

        if ($user) {
            $data['user'] = $user;

            $data['players'] = Player::query()
                ->select(['license_identifier', 'player_name'])
                ->where(DB::raw(sprintf("JSON_CONTAINS(identifiers, '\"discord:%s\"')", $user['id'])), '=', '1')
                ->get()->toArray();
        }

        return (new \Illuminate\Http\Response(json_encode($data), 200))
            ->header('Content-Type', 'application/json');
    }

}
