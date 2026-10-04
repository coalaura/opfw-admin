<?php
namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\LogExport;
use App\Helpers\PermissionHelper;
use App\Http\Resources\LogResource;
use App\Http\Resources\MoneyLogResource;
use App\Http\Resources\WeaponDamageEventResource;
use App\Log;
use App\MoneyLog;
use App\Player;
use App\WeaponDamageEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    const RestrictedLogs = [
        "Changed Frequency",
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

        $skipped = [];

        $query = $this->serverLogsQuery($request)->orderByDesc('timestamp');

        if (! $this->isSeniorStaff($request)) {
            $skipped = ['action is "' . implode('", "', self::RestrictedLogs) . '"'];
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

        $query->limit(30)->offset(($page - 1) * 30);

        $logs = $query->get();

        $logs = LogResource::collection($logs);

        $end = round(microtime(true) * 1000);

        return Inertia::render('Logs/Index', [
            'logs'        => $logs,
            'filters'     => $request->all(
                'identifier',
                'server',
                'action',
                'details',
                'minigame',
                'after',
                'before'
            ),
            'links'       => $this->getPageUrls($page),
            'time'        => $end - $start,
            'playerMap'   => Player::fetchLicensePlayerNameMap($logs->toArray($request), 'licenseIdentifier'),
            'page'        => $page,
            'actions'     => CacheHelper::getLogActions(),
            'skipped'     => $skipped,
            'exportSorts' => array_keys(LogExport::SORT_COLUMNS['server']),
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
        $start = round(microtime(true) * 1000);

        $query = $this->moneyLogsQuery($request)->orderByDesc('timestamp');

        $page = Paginator::resolveCurrentPage('page');
        $query->limit(30)->offset(($page - 1) * 30);

        $logs = $query->get();

        $logs = MoneyLogResource::collection($logs);

        $end = round(microtime(true) * 1000);

        return Inertia::render('Logs/MoneyLogs', [
            'logs'        => $logs,
            'filters'     => [
                'typ'          => $request->input('typ') ?? '',
                'direction'    => $request->input('direction') ?? '',
                'amount'       => $request->input('amount'),
                'identifier'   => $request->input('identifier'),
                'character_id' => $request->input('character_id'),
                'details'      => $request->input('details'),
                'after'        => $request->input('after'),
                'before'       => $request->input('before'),
            ],
            'links'       => $this->getPageUrls($page),
            'time'        => $end - $start,
            'page'        => $page,
            'exportSorts' => array_keys(LogExport::SORT_COLUMNS['money']),
        ]);
    }

    public function darkChat(Request $request)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_DARK_CHAT)) {
            abort(403);
        }

        $start = round(microtime(true) * 1000);

        $query = DB::table('gcphone_app_chat')
            ->select(['id', 'gcphone_app_chat.license_identifier', 'character_id', 'player_name', 'channel', 'message', DB::raw('UNIX_TIMESTAMP(`time`) AS timestamp')])
            ->orderByDesc('time');

        // Filtering by license.
        $this->searchQuery($request, $query, 'license', 'gcphone_app_chat.license_identifier');

        // Filtering by channel.
        $this->searchQuery($request, $query, 'channel', 'channel');

        // Filtering by message.
        $this->searchQuery($request, $query, 'message', 'message');

        $query->leftJoin('users', 'users.license_identifier', '=', 'gcphone_app_chat.license_identifier');

        $page = Paginator::resolveCurrentPage('page');

        $query->limit(30)->offset(($page - 1) * 30);

        $logs = $query->get()->toArray();

        $end = round(microtime(true) * 1000);

        return Inertia::render('Logs/DarkChat', [
            'logs'    => $logs,
            'filters' => $request->all(
                'license',
                'channel',
                'message'
            ),
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
    public function phoneMsgs(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_PHONE_LOGS)) {
            abort(403);
        }

        return Inertia::render('Logs/PhoneMsg', [
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
    public function phoneMsgsData(Request $request)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_PHONE_LOGS)) {
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

        if ($id = intval($request->input('id'))) {
            $query->where('id', '<', $id);
        }

        $query->limit(30);

        $logs = $query->get()->toArray();

        return $this->json(true, $logs);
    }

    /**
     * Display the phone call logs.
     *
     * @param Request $request
     * @return Response
     */
    public function phoneCalls(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_PHONE_LOGS)) {
            abort(403);
        }

        return Inertia::render('Logs/PhoneCall', [
            'filters' => $request->all(
                'number',
                'after',
                'before'
            ),
        ]);
    }

    /**
     * Returns calls.
     *
     * @param Request $request
     */
    public function phoneCallsData(Request $request)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_PHONE_LOGS)) {
            abort(403);
        }

        $query = DB::table("phone_call_logs")->select([
            'id', 'caller_number', 'receiver_number', 'accepted', 'anonymous', 'duration', 'reason', 'timestamp',
        ])->orderByDesc('timestamp')->orderByDesc('id');

        $number = $this->multiValues($request->input('number'));

        if ($number) {
            $query->where(function ($q) use ($number) {
                $q->where('caller_number', $number)->orWhere('receiver_number', $number);
            });
        }

        $after = intval($request->input('after'));

        if ($after) {
            $query->where(function ($q) use ($after) {
                $q->where('timestamp', '>', $after)->orWhere(DB::raw('timestamp + duration'), '>', $after);
            });
        }

        $before = intval($request->input('before'));

        if ($before) {
            $query->where(function ($q) use ($before) {
                $q->where('timestamp', '<', $before)->orWhere(DB::raw('timestamp - duration'), '<', $before);
            });
        }

        if ($id = intval($request->input('id'))) {
            $query->where('id', '<', $id);
        }

        $query->limit(30);

        $logs = $query->get()->toArray();

        return $this->json(true, $logs);
    }

    public function searches(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_ADVANCED)) {
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

        $query->limit(20)->offset(($page - 1) * 20);

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
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_ADVANCED)) {
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

        $paginated = array_slice($groupedLogs, ($page - 1) * 20, 20);

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
            'maxPage'   => ceil(sizeof($groupedLogs) / 20),
        ]);
    }

    public function damageLogs(Request $request)
    {
        $start = round(microtime(true) * 1000);

        $query = $this->damageLogsQuery($request)->orderByDesc('timestamp');
        $page  = Paginator::resolveCurrentPage('page');
        $query->limit(30)->offset(($page - 1) * 30);

        $logs = WeaponDamageEventResource::collection($query->get());

        $end = round(microtime(true) * 1000);

        return Inertia::render('Logs/Damage', [
            'logs'        => $logs,
            'filters'     => $request->all(
                'attacker',
                'victim',
                'damage',
                'weapon',
                'entity',
                'minigame',
                'after',
                'before'
            ),
            'links'       => $this->getPageUrls($page),
            'time'        => $end - $start,
            'playerMap'   => Player::fetchLicensePlayerNameMap($logs->toArray($request), ['licenseIdentifier', 'hitLicense']),
            'page'        => $page,
            'weapons'     => array_values(WeaponDamageEvent::getWeaponListFlat()),
            'exportSorts' => array_keys(LogExport::SORT_COLUMNS['damage']),
        ]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(isset(LogExport::SORT_COLUMNS[$type]), 404);

        $options = $request->validate(LogExport::rules($type));

        $query = match ($type) {
            'server' => $this->serverLogsQuery($request),
            'damage' => $this->damageLogsQuery($request),
            'money'  => $this->moneyLogsQuery($request),
        };

        $query->orderBy(LogExport::SORT_COLUMNS[$type][$options['sort']], $options['order']);
        $query->orderBy($query->getModel()->qualifyColumn('id'), $options['order']);
        $logs = $query->limit($options['limit'])->get();

        $playerNames = match ($type) {
            'server' => Player::fetchLicensePlayerNameMap($logs->all(), 'identifier'),
            'damage' => Player::fetchLicensePlayerNameMap($logs->all(), ['license_identifier', 'hit_player']),
            'money'  => [],
        };

        return LogExport::download($type, $logs, $playerNames);
    }

    protected function serverLogsQuery(Request $request): Builder
    {
        $query = Log::query();

        if (! $this->isSeniorStaff($request)) {
            $query->whereNotIn('action', self::RestrictedLogs);
        }

        $this->searchQuery($request, $query, 'identifier', 'identifier');
        $this->searchQuery($request, $query, 'action', 'action');
        $this->searchQuery($request, $query, 'details', 'details');
        $this->searchQuery($request, $query, 'server', DB::raw("JSON_EXTRACT(metadata, '$.playerServerId')"));

        $minigame = $request->input('minigame');

        if ($minigame && $minigame !== 'any') {
            // Only these actions store a minigame in their metadata.
            $minigameActions = ['Player Died', 'Player Killed', 'Killed Player'];

            if ($minigame === 'none') {
                $query->where(function ($subQuery) use ($minigameActions) {
                    $subQuery->whereNotIn('action', $minigameActions);
                    $subQuery->orWhereNull(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.minigame'))"));
                });
            } elseif (in_array($minigame, ['arena', 'battle_royale', 'zombie_pill', 'training'], true)) {
                $query->where(function ($subQuery) use ($minigameActions, $minigame) {
                    $subQuery->whereIn('action', $minigameActions);
                    $subQuery->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.minigame')) = ?", [$minigame]);
                });
            }
        }

        if ($before = intval($request->input('before'))) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '<', $before);
        }

        if ($after = intval($request->input('after'))) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '>', $after);
        }

        return $query->select(['id', 'identifier', 'action', 'details', 'metadata', 'timestamp']);
    }

    protected function moneyLogsQuery(Request $request): Builder
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_MONEY_LOGS)) {
            abort(403);
        }

        $query = MoneyLog::query();

        $this->searchQuery($request, $query, 'identifier', 'money_logs.license_identifier');
        $this->searchQuery($request, $query, 'character_id', 'money_logs.character_id');
        $this->searchQuery($request, $query, 'details', 'details');
        $this->searchQuery($request, $query, 'amount', 'amount');

        if ($before = $request->input('before')) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '<', $before);
        }

        if ($after = $request->input('after')) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '>', $after);
        }

        if ($type = $request->input('typ')) {
            $query->where('type', $type);
        }

        if ($direction = $request->input('direction')) {
            if ($direction === 'in') {
                $query->where('amount', '>', '0');
            } elseif ($direction === 'out') {
                $query->where('amount', '<', '0');
            }
        }

        $query->leftJoin('users', 'users.license_identifier', '=', 'money_logs.license_identifier');
        $query->leftJoin('characters', 'characters.character_id', '=', 'money_logs.character_id');

        return $query->select(['money_logs.id', 'type', 'money_logs.license_identifier', 'money_logs.character_id', 'amount', 'balance_after', 'details', 'timestamp', 'player_name', DB::raw('CONCAT(first_name, " ", last_name) AS character_name')]);
    }

    protected function damageLogsQuery(Request $request): Builder
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_DAMAGE_LOGS)) {
            abort(401);
        }

        $query = WeaponDamageEvent::query()->where('is_parent_self', '=', '1');

        // Filtering by attacker identifier.
        $this->searchQuery($request, $query, 'attacker', 'license_identifier');

        // Filtering by victim identifier.
        if ($victim = $request->input('victim')) {
            $victim = strtolower(trim($victim));

            if (Str::startsWith($victim, 'license:')) {
                $query->where('hit_player', $victim);
            } else if (Str::startsWith($victim, 'v')) {
                $query->where('hit_vehicle_id', substr($victim, 1));
            } else {
                if (! preg_match('/^\d/m', $victim)) {
                    $victim = substr($victim, 1);
                }

                $query->where('hit_global_id', $victim);
            }
        }

        // Filtering by damage.
        $this->searchQuery($request, $query, 'damage', 'weapon_damage');

        // Filtering by weapon.
        if ($weapon = $request->input('weapon')) {
            $hash = is_numeric($weapon) ? $weapon : WeaponDamageEvent::getWeaponHash($weapon);

            if ($hash) {
                if ($hash < 0) {
                    $hash += 4294967296;
                }

                $query->where('weapon_type', $hash);
            }
        }

        // Filtering by entity type.
        if ($entityType = $request->input('entity')) {
            switch ($entityType) {
                case "ped":
                    $query->where('hit_entity_type', '=', '1');
                    break;
                case "vehicle":
                    $query->where('hit_entity_type', '=', '2');
                    break;
                case "object":
                    $query->where('hit_entity_type', '=', '3');
                    break;
                case "player":
                    $query->whereNotNull('hit_player')->where('hit_player', '!=', '');
                    break;
            }
        }

        // Filtering by minigame.
        $minigame = $request->input('minigame');
        if ($minigame && $minigame !== 'any') {
            if ($minigame === 'none') {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('minigame');
                    $subQuery->orWhere('minigame', '=', '');
                });
            } elseif (in_array($minigame, ['arena', 'battle_royale', 'zombie_pill', 'training'], true)) {
                $query->where('minigame', '=', $minigame);
            }
        }

        // Filtering by before.
        if ($before = intval($request->input('before'))) {
            $query->where('timestamp', '<', $before * 1000);
        }

        // Filtering by after.
        if ($after = intval($request->input('after'))) {
            $query->where('timestamp', '>', $after * 1000);
        }

        return $query->select(['id', 'license_identifier', 'timestamp', 'hit_player', 'hit_health', 'distance', 'hit_vehicle_id', 'hit_global_id', 'hit_entity_type', 'hit_component', 'damage_flags', 'silenced', 'tyre_index', 'suspension_index', 'weapon_damage', 'weapon_type', 'bonus_damage', 'canceled', 'minigame']);
    }

    private function multiValues(?string $val): ?array
    {
        if (! $val) {
            return null;
        }

        return array_values(array_map(function ($v) {
            return trim($v);
        }, explode(',', $val)));
    }
}
