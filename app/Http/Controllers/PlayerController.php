<?php
namespace App\Http\Controllers;

use App\BlacklistedIdentifier;
use App\Character;
use App\Helpers\GeneralHelper;
use App\Helpers\ServerAPI;
use App\Helpers\StatisticsHelper;
use App\Helpers\StatusHelper;
use App\Http\Controllers\PlayerDataController;
use App\Http\Resources\BanResource;
use App\Http\Resources\CharacterSlimResource;
use App\Http\Resources\PlayerIndexResource;
use App\Http\Resources\PlayerResource;
use App\Player;
use App\Warning;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $start = round(microtime(true) * 1000);

        $query = Player::query();

        // Filtering by name.
        $this->searchQuery($request, $query, 'name', 'player_name');

        // Filtering by license_identifier.
        $this->searchQuery($request, $query, 'license', 'license_identifier');

        // Filtering by identifier.
        $identifier = $request->input('identifier');

        if ($identifier) {
            if (Str::startsWith($identifier, '~') or ! Str::contains($identifier, ':')) {
                $identifier = substr($identifier, 1);

                $query->where('identifiers', 'LIKE', '%' . $identifier . '%');
            } else {
                $id = '"' . $identifier . '"';

                $query->where(DB::raw("JSON_CONTAINS(identifiers, '$id')"), '=', '1');
            }
        }

        // Filtering by serer-id.
        if ($server = $request->input('server')) {
            $online = array_keys(array_filter(StatusHelper::all(), function ($player) use ($server) {
                return $player['source'] === intval($server);
            }));

            $query->whereIn('license_identifier', $online);
        }

        // Filtering by enabled permission
        $available = ServerAPI::getPermissions();
        $enablable = $request->input('enablable');
        if (isset($available[$enablable])) {
            $query->where(DB::raw('JSON_CONTAINS(enabled_commands, \'"' . $enablable . '"\')'), '=', '1');
        }

        // Filtering by ban exception
        $exception = $request->input('streamer_exception');

        if ($exception) {
            $query->where(DB::raw("JSON_EXISTS(user_data, '$.twitchBanException')"), '=', '1');
        }

        // Get ban info
        $query->leftJoin('user_bans', 'identifier', '=', 'license_identifier');

        // Sort query
        $sorting = $this->sortQuery($request, $query, 'playtime', [
            'id'       => 'user_id',
            'name'     => 'player_name',
            'playtime' => 'playtime',
            'last'     => 'last_seen',
        ]);

        $query->select([
            'license_identifier', 'player_name', 'playtime', 'identifiers', 'player_aliases', 'ban_hash', 'media_devices',
        ]);

        $page = Paginator::resolveCurrentPage('page');
        $query->limit(20)->offset(($page - 1) * 20);

        $players = $query->get();

        $end = round(microtime(true) * 1000);

        return Inertia::render('Players/Index', [
            'players'   => PlayerIndexResource::collection($players),
            'filters'   => array_merge($sorting, [
                'name'               => $request->input('name'),
                'license'            => $request->input('license'),
                'server'             => $request->input('server'),
                'identifier'         => $request->input('identifier'),
                'streamer_exception' => $request->input('streamer_exception') ?? '',
                'enablable'          => $request->input('enablable') ?? '',
            ]),
            'links'     => $this->getPageUrls($page),
            'page'      => $page,
            'time'      => $end - $start,
            'enablable' => ServerAPI::getPermissions(),
        ]);
    }

    /**
     * Display a listing of all online new players.
     *
     * @return Response
     */
    public function newPlayers(): Response
    {
        $players = Player::getNewPlayers();

        $characterIds = [];

        foreach ($players as $player) {
            $status = Player::getOnlineStatus($player->license_identifier, true);

            if ($status->character) {
                $characterId = $status->character;

                $characterIds[] = $characterId;
            }
        }

        $characters = ! empty($characterIds) ? Character::query()->whereIn('character_id', $characterIds)->get() : [];

        $playerList = [];

        foreach ($players as $player) {
            $character = null;

            foreach ($characters as $char) {
                if ($char->license_identifier === $player->license_identifier) {
                    $character = $char;

                    break;
                }
            }

            if (! $character || ! $character->character_created) {
                continue;
            }

            $status = Player::getOnlineStatus($player->license_identifier, true);

            $playerList[] = [
                'serverId'          => $status && $status->serverId ? $status->serverId : null,
                'character'         => [
                    'name'                    => $character->first_name . ' ' . $character->last_name,
                    'backstory'               => $character->backstory,
                    'character_creation_time' => $character->character_creation_time,
                    'gender'                  => $character->gender == 1 ? 'female' : 'male',
                    'date_of_birth'           => $character->date_of_birth,
                    'ped_model_hash'          => $character->ped_model_hash,
                    'creationTime'            => intval($character->character_creation_time),
                    'danny'                   => GeneralHelper::dannyPercentageCreationTime(intval($character->character_creation_time)),
                    'data'                    => $status->characterMetadata ?? [],
                ],
                'timezone'          => $player->getTimezone(),
                'playerName'        => $player->getSafePlayerName(),
                'playTime'          => $player->playtime,
                'licenseIdentifier' => $player->license_identifier,
                'suspicious'        => $player->areMediaDevicesSuspicious(),
            ];
        }

        return Inertia::render('Players/NewPlayers', [
            'players' => $playerList,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Player $player
     * @return Response|void
     */
    public function show(Request $request, Player $player)
    {
        if (! $player->license_identifier) {
            return $this->text(200, sprintf(
                'Player %s has no license identifier.',
                $player->getSafePlayerName(),
            ));
        }

        $whitelisted = DB::table('user_whitelist')
            ->select(['license_identifier'])
            ->where('license_identifier', '=', $player->license_identifier)
            ->first();

        $identifiers = $player->getIdentifiers();

        $blacklisted = ! empty($identifiers) ? BlacklistedIdentifier::query()
            ->select(['identifier'])
            ->whereIn('identifier', $identifiers)
            ->first() : false;

        $isSenior = $this->isSeniorStaff($request);

        return Inertia::render('Players/Show', [
            'player'            => new PlayerResource($player),
            'characters'        => CharacterSlimResource::collection($player->characters),
            'warnings'          => $player->fasterWarnings($isSenior),
            'reactions'         => Warning::Reactions,
            'animated'          => Warning::AnimatedReactions,
            'kickReason'        => trim($request->query('kick')) ?? '',
            'whitelisted'       => ! ! $whitelisted,
            'blacklisted'       => ! ! $blacklisted,
            'tags'              => Player::resolveTags(),
            'enablable'         => ServerAPI::getPermissions(),
            'uniqueBans'        => BanResource::collection($player->uniqueBans()),
        ]);
    }

    /**
     * Staff Activity Statistics loaded via ajax.
     *
     * @param Player $player
     * @param string $source
     * @param Request $request
     * @return Response|void
     */
    public function statistics(Player $player, string $source, Request $request)
    {
        if (! $player->isStaff() || ! $this->isSeniorStaff($request)) {
            abort(401);
        }

        $userId  = $player->user_id;
        $license = $player->license_identifier;
        $month   = strtotime("-1 month");

        $result = false;

        switch ($source) {
            case "bans":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(ban_hash) as amount, DATE_FORMAT(FROM_UNIXTIME(timestamp), '%c/%d/%Y') as date FROM (SELECT ban_hash, timestamp FROM user_bans WHERE creator_identifier = '$license' AND timestamp >= $month GROUP BY ban_hash) bans GROUP BY date ORDER BY timestamp DESC");
                break;
            case "notes":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(created_at, '%c/%d/%Y') as date FROM warnings WHERE issuer_id = $userId AND UNIX_TIMESTAMP(created_at) >= $month GROUP BY date ORDER BY created_at DESC");
                break;
            case "helpful":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action IN ('TP Player', 'TP Here', 'TP To', 'Started Spectating', 'Staff PM', 'Important Staff PM', 'Wiped Entities', 'Froze Player', 'Unfroze Player', 'Set Job', 'Reset Job', 'Revived Player', 'Revived Player And Removed Injuries', 'Revived Range') AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;

            // Command usages
            case "staff":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = 'Staff Message' AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;
            case "staff_pm":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action IN ('Staff PM', 'Important Staff PM') AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;
            case "noclip":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = 'Noclip Toggled' AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;
            case "spectate":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = 'Started Spectating' AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;
            case "revive_self":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action IN ('Revived Self And Removed Injuries', 'Revived Self') AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;
            case "revive":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action IN ('Revived Player And Removed Injuries', 'Revived Player') AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;
            case "armor_self":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = 'Set Body Armor Level For Self' AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;
            case "armor":
                $result = StatisticsHelper::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = 'Set Body Armor Level For Player' AND timestamp >= $month AND identifier = '$license' GROUP BY date ORDER BY timestamp DESC");
                break;
        }

        return $this->json(true, $result);
    }
}
