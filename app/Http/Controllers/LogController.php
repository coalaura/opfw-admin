<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\GeneralHelper;
use App\Helpers\PermissionHelper;
use App\Http\Resources\LogResource;
use App\Http\Resources\MoneyLogResource;
use App\Log;
use App\MoneyLog;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    const DRUG_LOGS = [
        "Gun Run",
        "Gun Run Drop",
        "Cocaine Run",
        "Oxy Run Started",
        "Oxy Run Ended",
        "Oxy Run Failed",
        "Jim's Gun Shop",
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $start = round(microtime(true) * 1000);

        $canSearchDrugs = true;

        $skipped = [];

        if (env('RESTRICT_DRUG_LOGS', false)) {
            $player = user();

            if (!$player->panel_drug_department && !$player->isSuperAdmin()) {
                $canSearchDrugs = false;
            }
        }

        $query = Log::query()->orderByDesc('timestamp');

        if (!$canSearchDrugs) {
            $query->whereNotIn('action', self::DRUG_LOGS);

            $skipped = ['action is "' . implode('", "', self::DRUG_LOGS) . '"'];
        }

        // Filtering by identifier.
        $this->searchQuery($request, $query, 'identifier', 'identifier');

        // Filtering by action.
        $this->searchQuery($request, $query, 'action', 'action');

        // Filtering by details.
        $this->searchQuery($request, $query, 'details', 'details');

        // Filtering by server.
        $this->searchQuery($request, $query, 'server', DB::raw("JSON_EXTRACT(metadata, '$.playerServerId')"));

        // Filtering by minigames.
        if ($request->input('minigames') === 'none') {
            $query->where(function ($subQuery) {
                // The only actions where we even use minigames are Player Died and Player Killed.
                $subQuery->whereNotIn('action', ['Player Died', 'Player Killed']);

                // If the action is Player Died or Player Killed, we have to check.
                $subQuery->orWhereNotIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.minigames[0]'))"), ['arena', 'battle_royale', 'training']);
            });
        }

        // Filtering by before.
        if ($before = intval($request->input('before'))) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '<', $before);
        }

        // Filtering by after.
        if ($after = intval($request->input('after'))) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '>', $after);
        }

        $actionInput     = $request->input('action');
        $detailsInput    = $request->input('details');
        $identifierInput = $request->input('identifier');
        $serverInput     = $request->input('server');

        $action     = $actionInput ? trim($actionInput) : null;
        $details    = $detailsInput ? trim($detailsInput) : null;
        $identifier = $identifierInput ? trim($identifierInput) : null;
        $server     = $serverInput ? trim($serverInput) : null;

        $page = Paginator::resolveCurrentPage('page');

        if ($action || $details || $identifier || $server) {
            DB::table('panel_log_searches')
                ->insert([
                    'action'             => $action,
                    'details'            => $details,
                    'identifier'         => $identifier,
                    'server'             => $server,
                    'page'               => $page,
                    'license_identifier' => license(),
                    'timestamp'          => time(),
                ]);

            DB::table('panel_log_searches')
                ->where('timestamp', '<', time() - CacheHelper::YEAR)
                ->delete();
        }

        $query->select(['id', 'identifier', 'action', 'details', 'metadata', 'timestamp']);
        $query->limit(15)->offset(($page - 1) * 15);

        $logs = $query->get();

        $logs = LogResource::collection($logs);

        $end = round(microtime(true) * 1000);

        return Inertia::render('Logs/Index', [
            'logs'           => $logs,
            'filters'        => [
                'identifier' => $request->input('identifier'),
                'server'     => $request->input('server'),
                'action'     => $request->input('action'),
                'details'    => $request->input('details'),
                'minigames'  => $request->input('minigames') ?? '',
                'after'      => $request->input('after'),
                'before'     => $request->input('before'),
            ],
            'links'          => $this->getPageUrls($page),
            'time'           => $end - $start,
            'playerMap'      => Player::fetchLicensePlayerNameMap($logs->toArray($request), 'licenseIdentifier'),
            'page'           => $page,
            'drugActions'    => self::DRUG_LOGS,
            'canSearchDrugs' => $canSearchDrugs,
            'actions'        => CacheHelper::getLogActions(),
            'skipped'        => $skipped,
        ]);
    }

    /**
     * Display money logs.
     *
     * @param Request $request
     * @return Response
     */
    public function moneyLogs(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_MONEY_LOGS)) {
            abort(403);
        }

        $start = round(microtime(true) * 1000);

        $query = MoneyLog::query()->orderByDesc('timestamp');

        if ($request->getHttpHost() !== 'localhost') {
            $query->whereNotIn('money_logs.license_identifier', GeneralHelper::getRootUsers());
        }

        // Filtering by identifier.
        $this->searchQuery($request, $query, 'identifier', 'money_logs.license_identifier');

        // Filtering by character id.
        $this->searchQuery($request, $query, 'character_id', 'character_id');

        // Filtering by details.
        $this->searchQuery($request, $query, 'details', 'details');

        // Filtering by before.
        if ($before = $request->input('before')) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '<', $before);
        }

        // Filtering by after.
        if ($after = $request->input('after')) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '>', $after);
        }

        // Filtering by type.
        if ($type = $request->input('typ')) {
            $query->where('type', $type);
        }

        $query->leftJoin('users', 'users.license_identifier', '=', 'money_logs.license_identifier');
        $query->leftJoin('characters', 'characters.character_id', '=', 'money_logs.character_id');

        $query->select(['id', 'type', 'money_logs.license_identifier', 'money_logs.character_id', 'amount', 'balance_after', 'details', 'timestamp', 'player_name', DB::raw('CONCAT(first_name, " ", last_name) AS character_name')]);

        $page = Paginator::resolveCurrentPage('page');
        $query->limit(15)->offset(($page - 1) * 15);

        $logs = $query->get();

        $logs = MoneyLogResource::collection($logs);

        $end = round(microtime(true) * 1000);

        return Inertia::render('Logs/MoneyLogs', [
            'logs'    => $logs,
            'filters' => [
                'identifier'   => $request->input('identifier'),
                'character_id' => $request->input('character_id'),
                'details'      => $request->input('details'),
                'typ'          => $request->input('typ') ?? '',
                'after'        => $request->input('after'),
                'before'       => $request->input('before'),
            ],
            'links'   => $this->getPageUrls($page),
            'time'    => $end - $start,
            'page'    => $page,
        ]);
    }

    /**
     * Display the phone message logs.
     *
     * @param Request $request
     * @return Response
     */
    public function phoneLogs(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_PHONE_LOGS)) {
            abort(403);
        }

        return Inertia::render('Logs/Phone', [
            'filters' => $request->all(
                'number1',
                'number2',
                'message'
            ),
        ]);
    }

    /**
     * Returns messages.
     *
     * @param Request $request
     */
    public function phoneLogsData(Request $request)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_PHONE_LOGS)) {
            abort(403);
        }

        $query = DB::table("phone_message_logs")->select([
            'id', 'sender_number', 'receiver_number', 'message', 'timestamp',
        ])->orderByDesc('timestamp')->orderByDesc('id');

        $number1 = $this->multiValues($request->input('number1'));

        if ($number1) {
            if (sizeof($number1) === 1) {
                $number1 = $number1[0];

                $number2 = $request->input('number2');

                if ($number2) {
                    $query->where(function ($q) use ($number1, $number2) {
                        $q->where(function ($q2) use ($number1, $number2) {
                            $q2->where('sender_number', $number1)->where('receiver_number', $number2);
                        })->orWhere(function ($q2) use ($number1, $number2) {
                            $q2->where('sender_number', $number2)->where('receiver_number', $number1);
                        });
                    });
                } else {
                    $query->where(function ($q) use ($number1) {
                        $q->where('sender_number', $number1)->orWhere('receiver_number', $number1);
                    });
                }
            } else {
                $query->where(function ($q) use ($number1) {
                    $q->whereIn('sender_number', $number1)->orWhereIn('receiver_number', $number1);
                });
            }
        }

        // Filtering by message.
        $this->searchQuery($request, $query, 'message', 'message');

        if ($before = intval($request->input('before'))) {
            $query->where('id', '<', $before);
        }

        $query->limit(30);

        $logs = $query->get()->toArray();

        return $this->json(true, $logs);
    }

    public function searches(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $query = DB::table('panel_log_searches')->orderByDesc('timestamp')->select();

        // Filtering by identifier.
        $this->searchQuery($request, $query, 'identifier', 'license_identifier');

        // Filtering by search query.
        $this->searchQuery($request, $query, 'details', 'details');

        // Filtering by before.
        if ($before = $request->input('before')) {
            $query->where('timestamp', '<', $before);
        }

        // Filtering by after.
        if ($after = $request->input('after')) {
            $query->where('timestamp', '>', $after);
        }

        $page = Paginator::resolveCurrentPage('page');

        $query->limit(15)->offset(($page - 1) * 15);

        $logs = $query->get();

        return Inertia::render('Logs/Searches', [
            'logs'      => $logs,
            'filters'   => $request->all(
                'identifier',
                'details',
                'after',
                'before'
            ),
            'links'     => $this->getPageUrls($page),
            'playerMap' => Player::fetchLicensePlayerNameMap($logs->toArray($request), 'license_identifier'),
            'page'      => $page,
        ]);
    }

    public function screenshotLogs(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $query = DB::table('panel_screenshot_logs')->orderByDesc('timestamp')->select();

        // Filtering by identifier.
        $this->searchQuery($request, $query, 'identifier', 'source_license');

        // Filtering by character.
        $this->searchQuery($request, $query, 'character', 'target_character');

        // Filtering by before.
        if ($before = $request->input('before')) {
            $query->where('timestamp', '<', $before);
        }

        // Filtering by after.
        if ($after = $request->input('after')) {
            $query->where('timestamp', '>', $after);
        }

        $page = Paginator::resolveCurrentPage('page');

        $logs = $query->get()->toArray();

        $groupedLogs = [];

        foreach ($logs as $log) {
            $entry = [
                "url"       => $log->url,
                "timestamp" => $log->timestamp,
                "type"      => $log->type,
            ];

            $foundEntry = false;

            foreach ($groupedLogs as &$groupedLog) {
                if ($groupedLog['source_license'] !== $log->source_license || $groupedLog['target_license'] !== $log->target_license || $groupedLog['target_character'] !== $log->target_character) {
                    continue;
                }

                $diff = abs($log->timestamp - $groupedLog['from']);

                if ($diff > 10 * 60) {
                    continue;
                }

                $foundEntry = true;

                $groupedLog['from'] = $log->timestamp;

                $groupedLog['entries'][] = $entry;

                break;
            }

            if ($foundEntry) {
                continue;
            }

            $groupedLogs[] = [
                "source_license"   => $log->source_license,
                "target_license"   => $log->target_license,
                "target_character" => $log->target_character,
                "from"             => $log->timestamp,
                "till"             => $log->timestamp,
                "entries"          => [
                    $entry,
                ],
            ];
        }

        $paginated = array_slice($groupedLogs, ($page - 1) * 15, 15);

        return Inertia::render('Logs/Screenshots', [
            'logs'      => $paginated,
            'filters'   => $request->all(
                'identifier',
                'character',
                'after',
                'before'
            ),
            'links'     => $this->getPageUrls($page),
            'playerMap' => Player::fetchLicensePlayerNameMap($logs, ['source_license', 'target_license']),
            'page'      => $page,
            'maxPage'   => ceil(sizeof($groupedLogs) / 15),
        ]);
    }

    private function multiValues(?string $val): ?array
    {
        if (!$val) {
            return null;
        }

        return array_values(array_map(function ($v) {
            return trim($v);
        }, explode(',', $val)));
    }
}
