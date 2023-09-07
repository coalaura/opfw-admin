<?php

namespace App\Http\Controllers;

use App\Character;
use App\Helpers\PermissionHelper;
use App\Player;
use App\Property;
use App\Vehicle;
use App\WeaponDamageEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdvancedSearchController extends Controller
{
    const Config = [
        'characters' => [
            'character_id',
            'backstory',
            'bank',
            'cash',
            'date_of_birth',
            'department_name',
            'first_name',
            'gender',
            'jail',
            'job_name',
            'last_name',
            'phone_number',
            'position_name',
            'license_identifier',
            'stocks_balance',
        ],
        'vehicles'   => [
            'vehicle_id',
            'garage_identifier',
            'garage_impound',
            'garage_state',
            'mileage',
            'model_name',
            'owner_cid',
            'plate',
        ],
        'users'      => [
            'user_id',
            'last_connection',
            'player_name',
            'playtime',
            'license_identifier',
            'total_joins',
            'is_staff',
            'is_super_admin',
        ],
        'properties' => [
            'property_id',
            'property_name',
            'property_type',
            'property_address',
            'property_cost',
            'property_renter',
            'property_renter_cid',
            'property_income',
            'property_last_pay',
        ],
    ];

    /**
     * Allowed advance search types
     */
    const AllowedAdvancedTypes = [
        'exact'     => '=',
        'more'      => '>=',
        'less'      => '<=',
        'like'      => 'LIKE',
        'not_null'  => 'not_null',
        'null'      => 'null',
        'not_empty' => 'not_empty',
        'empty'     => 'empty',
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $start = round(microtime(true) * 1000);

        $page = Paginator::resolveCurrentPage('page');

        $table = $request->get('table') ?? 'characters';
        $field = $request->get('field') ?? 'character_id';
        $type  = $request->get('searchType') ?? 'exact';
        $value = trim($request->get('value')) ?? '';

        if (in_array($type, [
            'not_null',
            'null',
            'not_empty',
            'empty',
        ])) {
            $value = '*';
        }

        $results = [];
        $header  = [];

        $error = '';
        if (!isset(self::AllowedAdvancedTypes[$type])) {
            $error = 'Invalid type';
        } else {
            $type = self::AllowedAdvancedTypes[$type];

            if (!isset(self::Config[$table]) || !in_array($field, self::Config[$table])) {
                $error = 'Invalid table of field';
            } else {
                if ($type === 'LIKE') {
                    $value = '%' . $value . '%';
                }
                if (($type === '>=' || $type === '<=') && !is_numeric($value)) {
                    $error = 'Value has to be numeric for more/less';
                } else if ($value) {
                    switch ($table) {
                        case 'characters':
                            $data    = $this->searchCharacters($field, $type, $value, $page);
                            $results = $data['results'];
                            $header  = $data['header'];
                            break;
                        case 'vehicles':
                            $data    = $this->searchVehicles($field, $type, $value, $page);
                            $results = $data['results'];
                            $header  = $data['header'];
                            break;
                        case 'users':
                            $data    = $this->searchUsers($field, $type, $value, $page);
                            $results = $data['results'];
                            $header  = $data['header'];
                            break;
                        case 'properties':
                            $data    = $this->searchProperties($field, $type, $value, $page);
                            $results = $data['results'];
                            $header  = $data['header'];
                            break;
                        default:
                            $error = 'Unknown table';
                    }
                }
            }
        }

        $end = round(microtime(true) * 1000);

        return Inertia::render('Advanced/Index', [
            'results'       => $results,
            'header'        => $header ?? [''],
            'filters'       => [
                'table'      => $table,
                'field'      => $field,
                'searchType' => $request->get('searchType') ?? 'exact',
                'value'      => $request->get('value') ?? '',
            ],
            'links'         => $this->getPageUrls($page),
            'page'          => $page,
            'config'        => self::Config,
            'time'          => $end - $start,
            'error'         => $error,
            'searchedTable' => $table,
        ]);
    }

    /**
     * Searches the vehicle table
     *
     * @param string $field
     * @param string $type
     * @param string $value
     * @param int $page
     * @return array
     */
    private function searchVehicles(string $field, string $type, string $value, int $page): array
    {
        $query = Vehicle::query()->orderBy('vehicle_id');
        self::where($query, $field, $type, $value);
        $query->where('vehicle_deleted', '=', '0');
        $query->select(self::Config['vehicles'])->limit(15)->offset(($page - 1) * 15);

        $data = $query->get()->toArray();

        $result = array_map(function ($entry) {
            $json = $entry;

            unset($json['vehicle_id']);
            unset($json['model_name']);
            unset($json['owner_cid']);

            return [
                [
                    'link' => $entry['owner_cid'] ? self::characterLinkArray($entry['owner_cid']) : "#",
                ],
                $entry['model_name'] . ' (' . $entry['vehicle_id'] . ')',
                self::formatJSON($json),
            ];
        }, $data);

        return [
            'results' => $result,
            'header'  => [
                'character',
                'info',
                'more',
            ],
        ];
    }

    /**
     * Searches the stocks_company_properties table
     *
     * @param string $field
     * @param string $type
     * @param string $value
     * @param int $page
     * @return array
     */
    private function searchProperties(string $field, string $type, string $value, int $page): array
    {
        $query = Property::query()->orderBy('property_id');
        self::where($query, $field, $type, $value);
        $query->select(self::Config['properties'])->limit(15)->offset(($page - 1) * 15);

        $data = $query->get()->toArray();

        $result = array_map(function ($entry) {
            $json = $entry;

            unset($json['property_renter_cid']);
            unset($json['property_address']);

            return [
                [
                    'link' => $entry['property_renter_cid'] ? self::characterLinkArray($entry['property_renter_cid']) : "#",
                ],
                $entry['property_address'],
                self::formatJSON($json),
            ];
        }, $data);

        return [
            'results' => $result,
            'header'  => [
                'character',
                'address',
                'more',
            ],
        ];
    }

    /**
     * Searches the users table
     *
     * @param string $field
     * @param string $type
     * @param string $value
     * @param int $page
     * @return array
     */
    private function searchUsers(string $field, string $type, string $value, int $page): array
    {
        $query = Player::query()->orderBy('user_id');
        self::where($query, $field, $type, $value);
        $query->select(self::Config['users'])->limit(15)->offset(($page - 1) * 15);

        $data = $query->get()->toArray();

        $result = array_map(function ($entry) {
            $json = $entry;

            unset($json['player_name']);
            unset($json['license_identifier']);
            unset($json['last_connection']);

            return [
                [
                    'link' => [
                        'target' => '/players/' . $entry['license_identifier'],
                        'label'  => $entry['player_name'],
                    ],
                ],
                [
                    'time' => intval($entry['last_connection']),
                ],
                self::formatJSON($json),
            ];
        }, $data);

        return [
            'results' => $result,
            'header'  => [
                'player',
                'last_connection',
                'more',
            ],
        ];
    }

    /**
     * Searches the character table
     *
     * @param string $field
     * @param string $type
     * @param string $value
     * @param int $page
     * @return array
     */
    private function searchCharacters(string $field, string $type, string $value, int $page): array
    {
        $query = Character::query()->orderBy('character_id');
        self::where($query, $field, $type, $value);
        $query->select(self::Config['characters'])->limit(15)->offset(($page - 1) * 15);

        $data = $query->get()->toArray();

        $players = [];

        $result = array_map(function ($entry) use ($players) {
            $json = $entry;

            unset($json['first_name']);
            unset($json['last_name']);
            unset($json['license_identifier']);
            unset($json['character_id']);

            if (!isset($players[$entry['license_identifier']])) {
                $player                                = Player::query()->where('license_identifier', '=', $entry['license_identifier'])->first(['player_name']);
                $players[$entry['license_identifier']] = $player ? $player->player_name : $entry['license_identifier'];
            }

            return [
                [
                    'link' => [
                        'target' => '/players/' . $entry['license_identifier'],
                        'label'  => $players[$entry['license_identifier']],
                    ],
                ],
                [
                    'link' => [
                        'target' => '/players/' . $entry['license_identifier'] . '/characters/' . $entry['character_id'] . '/edit',
                        'label'  => $entry['first_name'] . ' ' . $entry['last_name'] . ' (' . $entry['character_id'] . ')',
                    ],
                ],
                self::formatJSON($json),
            ];
        }, $data);

        return [
            'results' => $result,
            'header'  => [
                'player',
                'character',
                'more',
            ],
        ];
    }

    private static function where(Builder &$query, string $field, string $type, string $value)
    {
        switch ($type) {
            case 'null':
                $query->whereNull($field);
                break;
            case 'not_null':
                $query->whereNotNull($field);
                break;
            case 'empty':
                $query->where($field, '=', '');
                break;
            case 'not_empty':
                $query->where($field, '!=', '');
                break;
            default:
                $query->where($field, $type, $value);
        }
    }

    /**
     * @param int $characterId
     * @return array
     */
    private static function characterLinkArray(int $characterId): array
    {
        $character = Character::find($characterId);

        return [
            'target' => $character
            ? '/players/' . $character['license_identifier'] . '/characters/' . $character['character_id'] . '/edit'
            : '',
            'label'  => $character
            ? $character['first_name'] . ' ' . $character['last_name']
            : ($characterId ?? 'N/A'),
        ];
    }

    /**
     * Formats JSON array
     *
     * @param array $json
     * @return array
     */
    private static function formatJSON(array $json): array
    {
        foreach ($json as &$val) {
            if (is_numeric($val)) {
                if (Str::contains($val, '.')) {
                    $val = floatval($val);
                } else {
                    $val = intval($val);
                }
            } else if (is_string($val)) {
                if (strlen($val) > 50) {
                    $val = substr($val, 0, 47) . '...';
                }

                $val = htmlentities($val);
            }
        }

        return [
            'pre' => json_encode($json, JSON_PRETTY_PRINT),
        ];
    }

    /**
     * Weapons search.
     *
     * @param Request $request
     * @return Response
     */
    public function weapons(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $weapons = WeaponDamageEvent::getWeaponList();

        return Inertia::render('Advanced/Weapons', [
            'weapons' => $weapons,
        ]);
    }

    /**
     * Weapons search API.
     *
     * @param Request $request
     * @param int $hash
     */
    public function searchWeapons(Request $request, int $hash)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_ADVANCED)) {
            abort(401);
        }

        $weapons = WeaponDamageEvent::getWeaponList();

        if (!isset($weapons[$hash])) {
            abort(404);
        }

        $unsigned = $hash + 4294967296;

        $data = WeaponDamageEvent::query()
            ->select([DB::raw('COUNT(1) as count'), 'weapon_damage', 'ban_hash'])
            ->leftJoin('user_bans', 'identifier', '=', 'license_identifier')
            ->where('weapon_type', '=', $hash)
            ->orWhere('weapon_type', '=', $unsigned)
            ->groupBy(['weapon_damage', 'ban_hash'])
            ->get()->toArray();

        $dmgBanned = [];
        $dmgNormal = [];

        $count = 0;
        $avg   = 0;

        foreach ($data as $entry) {
            $damage = intval($entry['weapon_damage']);

            if ($entry['ban_hash']) {
                $dmgBanned[$damage] = $entry['count'];
            } else {
                $dmgNormal[$damage] = $entry['count'];

                if ($count < $entry['count']) {
                    $count = $entry['count'];
                    $avg   = $damage;
                }
            }
        }

        $max = $this->closest(array_keys($dmgNormal), $avg * 5);

        $damages = [
            'data'       => [
                [], [],
            ],
            'labels'     => [],
            'names'      => ['weapons.damage_normal', 'weapons.damage_banned'],
            'avg'        => $avg,
            'max'        => $max[1],
            'highlights' => [
                ['from' => false, 'to' => $max[0], 'color' => 'rgba(50, 255, 50, 0.1)'],
                ['from' => $max[0] + 1, 'to' => false, 'color' => 'rgba(255, 50, 50, 0.1)'],
            ],
        ];

        $maxDamage = max(array_keys($dmgBanned) + array_keys($dmgNormal));

        for ($x = 0; $x <= $maxDamage; $x++) {
            $normal = $dmgNormal[$x] ?? 0;
            $banned = $dmgBanned[$x] ?? 0;

            if ($normal === 0 && $banned === 0) {
                continue;
            }

            $damages['labels'][] = $x === 999 ? '999+ hp' : $x . 'hp';

            $damages['data'][0][] = $normal;
            $damages['data'][1][] = $banned;
        }

        return $this->json(true, [
            'damages' => $damages,
        ]);
    }

    private function closest(array $array, int $number): array
    {
        $last = 0;

        foreach ($array as $index => $item) {
            if ($item > $number) {
                return [$index - 1, $last];
            }

            $last = $item;
        }

        return [sizeof($array) - 1, $last];
    }

}
