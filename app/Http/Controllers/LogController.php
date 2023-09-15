<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\PermissionHelper;
use App\Http\Resources\LogResource;
use App\Log;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        if ($identifier = $this->multiValues($request->input('identifier'))) {
            /**
             * @var $q Builder
             */
            $query->where(function ($q) use ($identifier) {
                foreach ($identifier as $i) {
                    $q->orWhere('identifier', $i);
                }
            });
        }

        // Filtering by before.
        if ($before = $request->input('before')) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '<', $before);
        }

        // Filtering by after.
        if ($after = $request->input('after')) {
            $query->where(DB::raw('UNIX_TIMESTAMP(`timestamp`)'), '>', $after);
        }

        // Filtering by server.
        if ($server = $this->multiValues($request->input('server'))) {
            /**
             * @var $q Builder
             */
            $query->where(function ($q) use ($server) {
                foreach ($server as $s) {
                    $q->orWhere('details', 'LIKE', '% [' . intval($s) . '] %');
                }
            });
        }

        // Filtering by action.
        if ($action = $this->multiValues($request->input('action'))) {
            /**
             * @var $q Builder
             */
            $query->where(function ($q) use ($action) {
                foreach ($action as $a) {
                    if (Str::startsWith($a, '=')) {
                        $a = Str::substr($a, 1);
                        $q->orWhere('action', $a);
                    } else {
                        $q->orWhere('action', 'like', "%{$a}%");
                    }
                }
            });
        }

        // Filtering by details.
        if ($details = $request->input('details')) {
            if (Str::startsWith($details, '=')) {
                $details = Str::substr($details, 1);
                $query->where('details', $details);
            } else {
                $query->where('details', 'like', "%{$details}%");
            }
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
            'filters'        => $request->all(
                'identifier',
                'server',
                'action',
                'details',
                'after',
                'before'
            ),
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
     * Display the phone message logs.
     *
     * @param Request $request
     * @return Response
     */
    public function phoneLogs(Request $request): Response
    {
        if (!$this->isSuperAdmin($request)) {
            abort(403);
        }

        return Inertia::render('Logs/Phone', [
            'filters' => $request->all(
                'number',
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
        if (!$this->isSuperAdmin($request)) {
            abort(403);
        }

        $query = DB::table("phone_message_logs")->select([
            'id', 'sender_number', 'receiver_number', 'message', 'timestamp'
        ])->orderBy('timestamp');

        $number1 = $this->multiValues($request->input('number1'));

        if ($number1) {
            if (sizeof($number1) === 1) {
                $number1 = $number1[0];

                $number2 = $request->input('number2');

                if ($number2) {
                    $query->where(function ($q) use ($number1, $number2) {
                        $q->where('sender_number', $number1)->where('receiver_number', $number2);
                    })->orWhere(function ($q) use ($number1, $number2) {
                        $q->where('sender_number', $number2)->where('receiver_number', $number1);
                    });
                } else {
                    $query->where('sender_number', $number1)->orWhere('receiver_number', $number1);
                }
            } else {
                $query->whereIn('sender_number', $number1)->orWhereIn('receiver_number', $number1);
            }
        }

        // Filtering by message.
        if ($message = $request->input('message')) {
            if (Str::startsWith($message, '=')) {
                $message = Str::substr($message, 1);

                $query->where('message', $message);
            } else {
                $query->where('message', 'like', "%{$message}%");
            }
        }

        $page = intval($request->input('page', 1));

        $query->limit(30)->offset(($page - 1) * 30);

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
        if ($identifier = $this->multiValues($request->input('identifier'))) {
            /**
             * @var $q Builder
             */
            $query->where(function ($q) use ($identifier) {
                foreach ($identifier as $i) {
                    $q->orWhere('license_identifier', $i);
                }
            });
        }

        // Filtering by search query.
        if ($details = $this->multiValues($request->input('details'))) {
            /**
             * @var $q Builder
             */
            $query->where(function ($q) use ($details) {
                foreach ($details as $a) {
                    if (Str::startsWith($a, '=')) {
                        $a = Str::substr($a, 1);
                        $q->orWhere('action', $a);
                        $q->orWhere('details', $a);
                        $q->orWhere('identifier', $a);
                    } else {
                        $q->orWhere('action', 'like', "%{$a}%");
                        $q->orWhere('details', 'like', "%{$a}%");
                        $q->orWhere('identifier', 'like', "%{$a}%");
                    }
                }
            });
        }

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
        if ($identifier = $this->multiValues($request->input('identifier'))) {
            /**
             * @var $q Builder
             */
            $query->where(function ($q) use ($identifier) {
                foreach ($identifier as $i) {
                    $q->orWhere('source_license', $i);
                }
            });
        }

        // Filtering by character.
        if ($character = $this->multiValues($request->input('character'))) {
            /**
             * @var $q Builder
             */
            $query->where(function ($q) use ($character) {
                foreach ($character as $i) {
                    $q->orWhere('target_character', $i);
                }
            });
        }

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
