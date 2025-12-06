<?php
namespace App\Http\Controllers;

use App\Character;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\Helpers\StatusHelper;
use App\Http\Requests\CharacterUpdateRequest;
use App\Http\Resources\CharacterIndexResource;
use App\Http\Resources\CharacterResource;
use App\Http\Resources\PlayerResource;
use App\Motel;
use App\PanelLog;
use App\Player;
use App\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlayerCharacterController extends Controller
{
    const ValidTattooZones = [
        'all', 'head', 'left_arm', 'right_arm', 'torso', 'left_leg', 'right_leg',
    ];

    const Licenses = [
        "heli", "fw", "cfi", "hwh", "hw", "perf", "utility", "commercial", "management", "passenger", "military", "special", "hunting", "fishing", "mining", "bar", "weapon", "driver", "boat", "press",
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

        $query = Character::query();

        // Filtering by cid.
        if ($cid = $request->input('character_id')) {
            $query->where('character_id', $cid);
        }

        // Filtering by name.
        $this->searchQuery($request, $query, 'name', DB::raw("CONCAT(first_name, ' ', last_name)"));

        // Filtering by Phone Number.
        $this->searchQuery($request, $query, 'phone', 'phone_number');

        // Filtering by DoB.
        $this->searchQuery($request, $query, 'dob', 'date_of_birth');

        // Filtering by Job.
        $this->searchQuery($request, $query, 'job', DB::raw("CONCAT(job_name, ' ', department_name, ' ', position_name)"));

        // Filtering by Vehicle Plate or ID.
        $vehicleID    = $request->input('vehicle_id');
        $vehiclePlate = $request->input('vehicle_plate');

        if ($vehiclePlate || $vehicleID) {
            $query->whereHas('vehicles', function ($subQuery) use ($vehicleID, $vehiclePlate) {
                if ($vehicleID) {
                    $subQuery->where('vehicle_id', $vehicleID);
                }

                if ($vehiclePlate) {
                    $subQuery->where('plate', $vehiclePlate);
                }
            });
        }

        // Filtering license.
        $license = $request->input('license');
        $license = $license && in_array($license, self::Licenses) ? $license : null;

        if ($license) {
            $license = '"' . $license . '"';

            $query->where(DB::raw("JSON_CONTAINS(character_data, '$license', '$.licenses')"), '=', '1');
        }

        // Sort query
        $sorting = $this->sortQuery($request, $query, 'name', [
            'id'       => 'character_id',
            'name'     => DB::raw('CONCAT(first_name, last_name)'),
            'playtime' => 'playtime',
            'last'     => 'last_seen',
        ]);

        $query->select([
            'character_id', 'license_identifier', 'first_name', 'last_name', 'gender', 'job_name',
            'department_name', 'position_name', 'phone_number', 'date_of_birth',
        ]);

        $page = Paginator::resolveCurrentPage('page');
        $query->limit(20)->offset(($page - 1) * 20);

        $characters = CharacterIndexResource::collection($query->get());

        $end = round(microtime(true) * 1000);

        return Inertia::render('Characters/Index', [
            'characters' => $characters,
            'filters'    => array_merge($sorting, [
                'character_id'  => $request->input('character_id'),
                'name'          => $request->input('name'),
                'vehicle_plate' => $request->input('vehicle_plate'),
                'vehicle_id'    => $request->input('vehicle_id'),
                'phone'         => $request->input('phone'),
                'dob'           => $request->input('dob'),
                'job'           => $request->input('job'),
                'license'       => $request->input('license') ?? '',
            ]),
            'links'      => $this->getPageUrls($page),
            'page'       => $page,
            'time'       => $end - $start,
            'playerMap'  => Player::fetchLicensePlayerNameMap($characters->toArray($request), 'licenseIdentifier'),
            'licenses'   => self::Licenses,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Player $player
     * @param Character $character
     * @return Response
     */
    public function show(Player $player, Character $character): Response
    {
        $resetCoords = json_decode(file_get_contents(__DIR__ . '/../../../helpers/coords_reset.json'), true);
        $motels      = Motel::query()->where('cid', $character->character_id)->get()->sortBy(['motel', 'room_id']);
        $motelMap    = json_decode(file_get_contents(__DIR__ . '/../../../helpers/motels.json'), true);

        $savingsAccounts = DB::table("savings_accounts")
            ->where("character_id", $character->character_id)
            ->orWhere(DB::raw("JSON_CONTAINS(access, " . $character->character_id . ")"), '=', '1')
            ->get()->toArray();

        $horns = Vehicle::getHornMap(false);

        $jobs     = ServerAPI::getJobs();
        $vehicles = Vehicle::getVehicleModels();

        return Inertia::render('Characters/Show', [
            'player'          => new PlayerResource($player),
            'character'       => new CharacterResource($character),
            'motels'          => $motels->toArray(),
            'motelMap'        => $motelMap,
            'savingsAccounts' => $savingsAccounts,
            'horns'           => $horns,
            'vehicles'        => $vehicles ?? [],
            'jobs'            => $jobs ? $jobs['jobs'] : [],
            'resetCoords'     => $resetCoords ? array_keys($resetCoords) : [],
            'vehicleValue'    => Vehicle::getTotalVehicleValue($character->character_id),
            'pedModels'       => $this->getPedModels(),
        ]);
    }

    public function backstories(): Response
    {
        return Inertia::render('Characters/Backstories');
    }

    public function backstoriesApi(Request $request): \Illuminate\Http\Response
    {
        $character = Character::query()->orderByRaw('RAND()')->limit(1)->get()->first();

        if ($character) {
            return $this->json(true, (new CharacterResource($character))->toArray($request));
        }

        return $this->json(false, null, 'Failed to get character');
    }

    /**
     * Find a character by their cid
     *
     * @param Request $request
     * @param int $cid
     * @return \Illuminate\Contracts\Foundation\Application|RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function find(Request $request, int $cid)
    {
        $character = Character::query()->select(['license_identifier', 'character_id'])->where('character_id', '=', $cid)->get()->first();

        if (! $character) {
            abort(404);
        }

        return redirect('/players/' . $character->license_identifier . '/characters/' . $character->character_id);
    }

    /**
     * Updates the specified resource.
     *
     * @param Player $player
     * @param Character $character
     * @param CharacterUpdateRequest $request
     * @return RedirectResponse
     */
    public function update(Player $player, Character $character, CharacterUpdateRequest $request): RedirectResponse
    {
        $user = user();
        $data = $request->validated();

        $data['first_name'] = trim(ucwords(strtolower($data['first_name'])));
        $data['last_name']  = trim(ucwords(strtolower($data['last_name'])));

        // Fix broken roman numerals like III and IV being lowercased Iii
        $data['last_name'] = preg_replace_callback('/\b[IV]+\b/i', function ($matches) {
            return strtoupper($matches[0]);
        }, $data['last_name']);

        if (! empty($data['date_of_birth'])) {
            $time = strtotime($data['date_of_birth']);
            if (! $time) {
                return backWith('error', 'Invalid date of birth');
            }

            $data['date_of_birth'] = date('Y-m-d', $time);
        }

        if (! $data['job_name'] || $data['job_name'] === 'Unemployed') {
            $data['job_name']        = null;
            $data['department_name'] = null;
            $data['position_name']   = null;
        }

        $character->update($data);

        $info = 'In-Game character refresh failed, user has to soft-nap.';

        $refresh = OPFWHelper::updateCharacter($player, $character->character_id);

        if ($refresh->status) {
            $info = $refresh->notExecuted ? '' : 'In-Game character refresh was successful too.';
        }

        PanelLog::log(
            $user->license_identifier,
            "Edited Character",
            sprintf("%s edited %s (#%d).", $user->consoleName(), $player->consoleName(), $character->character_id),
            ['data' => $data]
        );

        return backWith('success', 'Character was successfully updated. ' . $info);
    }

    /**
     * Refreshes the characters email address.
     *
     * @param Request $request
     * @param Player $player
     * @param Character $character
     * @return RedirectResponse
     */
    public function refreshEmail(Request $request, Player $player, Character $character): RedirectResponse
    {
        $before = $character->email_address;

        if (! $character->refreshEmailAddress()) {
            return backWith('error', 'Failed to update email address.');
        }

        $user = user();

        PanelLog::log(
            $user->license_identifier,
            "Refreshed E-Mail",
            sprintf("%s refreshed the email address of %s (#%d).", $user->consoleName(), $player->consoleName(), $character->character_id),
            [
                'before' => $before,
                'after'  => $character->email_address,
            ]
        );

        return backWith('success', 'Email address was successfully updated.');
    }

    /**
     * Deletes the specified resource.
     *
     * @param Player $player
     * @param Character $character
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request, Player $player, Character $character): RedirectResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can delete characters.');
        }

        $user = user();

        if ($character->character_deleted) {
            return backWith('error', 'Character is already deleted.');
        }

        if (DB::statement('UPDATE `characters` SET `character_deleted` = 1, `character_deletion_timestamp`=' . time() . ' WHERE `character_id` = ' . $character->character_id)) {
            PanelLog::log(
                $user->license_identifier,
                "Deleted Character",
                sprintf("%s deleted character #%d from %s.", $user->consoleName(), $character->character_id, $player->consoleName()),
            );

            return backWith('success', 'Character was successfully deleted.');
        }

        return backWith('error', 'Failed to delete character.');
    }

    /**
     * Removes a characters tattoos
     *
     * @param Player $player
     * @param Character $character
     * @param Request $request
     * @return RedirectResponse
     */
    public function removeTattoos(Player $player, Character $character, Request $request): RedirectResponse
    {
        $user = user();

        $zone = $request->get('zone');
        $json = $character->tattoos_data;
        $map  = json_decode(file_get_contents(__DIR__ . '/../../../helpers/tattoo-map.json'), true);

        if (! $map || ! is_array($map)) {
            return backWith('error', 'Failed to load zone map');
        }

        if (! $zone || ! in_array($zone, self::ValidTattooZones)) {
            return backWith('error', 'Invalid or no zone provided');
        }

        $cleanedMap = [];

        foreach ($map as $key => $value) {
            $cleanedMap[strtolower($key)] = $value;
        }

        if ($zone === 'all') {
            $json = [];
        } else if (is_array($json)) {
            $result = [];
            foreach ($json as $tattoo) {
                if (! isset($tattoo['overlay'])) {
                    continue;
                }

                $key = strtolower($tattoo['overlay']);
                $z   = isset($cleanedMap[$key]) ? $cleanedMap[$key]['zone'] : null;

                if (! $z || $z !== $zone) {
                    $result[] = $tattoo;
                }
            }

            $json = $result;
        } else {
            $json = [];
        }

        $character->update([
            'tattoos_data' => $json,
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Removed Tattoos",
            sprintf("%s removed %s tattoos from %s (#%d).", $user->consoleName(), $zone, $player->consoleName(), $character->character_id),
        );

        $info    = 'In-Game Tattoo refresh failed, user has to softnap.';
        $refresh = OPFWHelper::updateTattoos($player, $character->character_id);
        if ($refresh->status) {
            $info = 'In-Game tattoo refresh was successful too.';
        }

        return backWith('success', 'Tattoos were removed successfully. ' . $info);
    }

    /**
     * Resets a characters spawn-point
     *
     * @param Player $player
     * @param Character $character
     * @param Request $request
     * @return RedirectResponse
     */
    public function resetSpawn(Player $player, Character $character, Request $request): RedirectResponse
    {
        $user = user();

        $spawn       = $request->get('spawn');
        $resetCoords = json_decode(file_get_contents(__DIR__ . '/../../../helpers/coords_reset.json'), true);

        if (! $resetCoords || ! is_array($resetCoords)) {
            return backWith('error', 'Failed to load spawn points');
        }

        if (! $spawn || (! isset($resetCoords[$spawn]) && $spawn !== "staff")) {
            return backWith('error', 'Invalid or no spawn provided');
        }

        $coords = $spawn === "staff" ? '{"w":262.6,"x":-77.6,"y":-817.2,"z":321.285}' : json_encode($resetCoords[$spawn]);

        $character->update([
            'coords' => $coords,
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Reset Spawn",
            sprintf("%s reset the spawn for %s (#%d).", $user->consoleName(), $player->consoleName(), $character->character_id),
        );

        return backWith('success', 'Spawn was reset successfully.');
    }

    /**
     * Divorces 2 characters
     *
     * @param Player $player
     * @param Character $character
     * @param Request $request
     * @return RedirectResponse
     */
    public function divorce(Player $player, Character $character, Request $request): RedirectResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can divorce characters.');
        }

        $user = user();

        $marriedTo = $character->married_to;

        if (! $marriedTo) {
            return backWith('error', 'Character is not married.');
        }

        $character->update([
            'married_to' => null,
        ]);

        $marriedCharacter = Character::query()
            ->where('character_id', '=', $marriedTo)
            ->where('married_to', '=', $character->character_id)
            ->get()->first();

        if ($marriedCharacter) {
            $marriedCharacter->update([
                'married_to' => null,
            ]);
        }

        PanelLog::log(
            $user->license_identifier,
            "Reset Spawn",
            sprintf("%s divorced %s (#%d) from #%s.", $user->consoleName(), $player->consoleName(), $character->character_id, $marriedTo),
        );

        return backWith('success', 'Divorced successfully.');
    }

    /**
     * Reset a character's dead flag
     *
     * @param Player $player
     * @param Character $character
     * @param Request $request
     * @return RedirectResponse
     */
    public function reviveOffline(Player $player, Character $character, Request $request): RedirectResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can revive offline characters.');
        }

        $user = user();

        $character->update([
            'is_dead' => 0,
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Revived Offline",
            sprintf("%s revived %s (#%d).", $user->consoleName(), $player->consoleName(), $character->character_id),
        );

        return backWith('success', 'Revived successfully.');
    }

    /**
     * Edits a characters balance
     *
     * @param Player $player
     * @param Character $character
     * @param Request $request
     * @return RedirectResponse
     */
    public function editBalance(Player $player, Character $character, Request $request): RedirectResponse
    {
        $user = user();

        $cash   = intval($request->post("cash"));
        $bank   = intval($request->post("bank"));
        $stocks = intval($request->post("stocks"));

        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can edit a characters balance.');
        }

        $changed = [
            sprintf("cash: %d -> %d", $character->cash, $cash),
            sprintf("bank: %d -> %d", $character->bank, $bank),
            sprintf("stocks: %d -> %d", $character->stocks_balance, $stocks),
        ];

        $character->update([
            'cash'           => $cash,
            'bank'           => $bank,
            'stocks_balance' => $stocks,
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Edited Balance",
            sprintf("%s edited the balance of %s (#%d).", $user->consoleName(), $player->consoleName(), $character->character_id),
            ['changed' => $changed]
        );

        return backWith('success', 'Balance has been updated successfully.');
    }

    /**
     * Deletes the specified vehicle.
     *
     * @param Request $request
     * @param Vehicle $vehicle
     * @return RedirectResponse
     */
    public function deleteVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can delete vehicles.');
        }

        $user = user();

        $vehicle->update([
            'vehicle_deleted' => '1',
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Deleted Vehicle",
            sprintf("%s deleted vehicle #%d from character #%d.", $user->consoleName(), $vehicle->vehicle_id, $vehicle->owner_cid),
        );

        return backWith('success', 'Vehicle was successfully deleted.');
    }

    /**
     * Adds the specified vehicle.
     *
     * @param Request $request
     * @param Player $player
     * @param Character $character
     * @return RedirectResponse
     */
    public function addVehicle(Request $request, Player $player, Character $character): RedirectResponse
    {
        $model = $request->post('model');

        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can add vehicles.');
        }

        $user = user();

        $genPlate = function () {
            $a_z = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

            // For example: 28ULD493.
            return Str::upper(
                rand(0, 9) .
                rand(0, 9) .
                $a_z[rand(0, 25)] .
                $a_z[rand(0, 25)] .
                $a_z[rand(0, 25)] .
                rand(0, 9) .
                rand(0, 9) .
                rand(0, 9)
            );
        };

        $plate = $genPlate();
        $tries = 0;
        while ($tries < 100) {
            $tries++;

            $exists = Vehicle::query()->where('plate', '=', $plate)->count(['vehicle_id']) > 0;
            if (! $exists) {
                break;
            }

            $plate = $genPlate();
        }

        DB::table('character_vehicles')->insert([
            [
                'owner_cid'                => $character->character_id,
                'model_name'               => $model,
                'plate'                    => $plate,
                'mileage'                  => 0,
                'garage_identifier'        => '*',
                'garage_state'             => 1,
                'garage_impound'           => 0,
                'deprecated_damage'        => null,
                'deprecated_modifications' => null,
                'deprecated_fuel'          => 100,
                'deprecated_supporter'     => 0,
                'vehicle_deleted'          => 0,
            ],
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Added Vehicle",
            sprintf("%s added a `%s` to %s (#%d).", $user->consoleName(), $model, $player->consoleName(), $character->character_id),
        );

        return backWith('success', 'Vehicle was successfully added (Model: ' . $model . ', Plate: ' . $plate . ').');
    }

    /**
     * Changed the characters ped model.
     *
     * @param Request $request
     * @param Player $player
     * @param Character $character
     * @return RedirectResponse
     */
    public function editPedModel(Request $request, Player $player, Character $character): RedirectResponse
    {
        $model = $request->post('model');

        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can edit ped models.');
        }

        $user = user();

        $pedModels = $this->getPedModels();

        if (!$model || !in_array($model, $pedModels)) {
            return backWith('error', 'Invalid ped model.');
        }

        $character->update([
            'ped_model_hash' => joaat($model),
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Changed Ped Model",
            sprintf("%s changed the ped model of %s (#%d) to `%s`.", $user->consoleName(), $player->consoleName(), $character->character_id, $model),
        );

        $status = StatusHelper::get($player->license_identifier);

        if ($status && $status['character']) {
            OPFWHelper::unloadCharacter(user()->license_identifier, $player, $status['character']['id'], "Your character's ped model has been changed.");
        }

        return backWith('success', 'Ped model successfully changed.');
    }

    /**
     * Updates a characters licenses.
     *
     * @param Request $request
     * @param Player $player
     * @param Character $character
     * @return RedirectResponse
     */
    public function updateLicenses(Request $request, Player $player, Character $character): RedirectResponse
    {
        $user     = user();
        $licenses = $request->post('licenses');

        if (! is_array($licenses)) {
            return backWith('error', 'Invalid licenses.');
        }

        $licenses = array_values(array_unique(array_filter($licenses, function ($license) {
            return in_array($license, self::Licenses);
        })));

        $json = $character->character_data ?? [];

        $json['licenses'] = $licenses;

        $character->update([
            'character_data' => $json,
        ]);

        $info = 'In-Game character refresh failed, user has to soft-nap.';

        $refresh = OPFWHelper::updateCharacter($player, $character->character_id);

        if ($refresh->status) {
            $info = $refresh->notExecuted ? '' : 'In-Game character refresh was successful too.';
        }

        PanelLog::log(
            $user->license_identifier,
            "Edited Licenses",
            sprintf("%s edited the licenses of %s (#%d).", $user->consoleName(), $player->consoleName(), $character->character_id),
            ['licenses' => $licenses]
        );

        return backWith('success', 'Licenses were successfully updated. ' . $info);
    }

    /**
     * Resets the specified vehicles garage.
     *
     * @param Request $request
     * @param Vehicle $vehicle
     * @param bool $fullReset
     * @return \Illuminate\Http\Response
     */
    public function resetGarage(Request $request, Vehicle $vehicle, bool $fullReset): RedirectResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return backWith('error', 'Only super admins can reset vehicles garages.');
        }

        $data = [];

        if ($fullReset) {
            $data['garage_identifier']      = '*';
            $data['garage_state']           = 1;
            $data['garage_impound']         = 0;
            $data['last_garage_identifier'] = null;
        } else {
            $data['garage_identifier']      = 6; // Garage C
            $data['garage_state']           = 1;
            $data['garage_impound']         = 0;
            $data['last_garage_identifier'] = 6;
        }

        $vehicle->update($data);

        return backWith('success', 'Vehicle garage was successfully reset.');
    }

    /**
     * Edits the specified vehicle.
     *
     * @param Request $request
     * @param Vehicle $vehicle
     * @return \Illuminate\Http\Response
     */
    public function editVehicle(Request $request, Vehicle $vehicle): \Illuminate\Http\Response
    {
        if (! $this->isSuperAdmin($request)) {
            return self::json(false, null, 'Only super admins can edit vehicles.');
        }

        $plate = trim(strtoupper($request->post('plate')));
        if (strlen($plate) < 3 || strlen($plate) > 8 || preg_match('/[^\w ]/mi', $plate)) {
            return self::json(false, null, 'Plate has to be between 3 and 8 characters long and only contain alphanumeric characters and spaces (A-Z and 0-9, cannot start or end with space).');
        }

        $exists = Vehicle::query()->where('plate', '=', $plate)->where('vehicle_id', '<>', $vehicle->vehicle_id)->where('vehicle_deleted', '=', '0')->count(['vehicle_id']) > 0;
        if ($exists) {
            return self::json(false, null, 'Plate "' . $plate . '" is already taken.');
        }

        $fuel = floatval($request->post('fuel'));
        if ($fuel < 0 || $fuel > 100) {
            return self::json(false, null, 'Invalid fuel value.');
        }

        $owner = $request->post('owner_cid');

        $character = Character::query()->where('character_id', '=', $owner)->first(['character_id', 'license_identifier']);
        if (! $character) {
            return self::json(false, null, 'Invalid character id.');
        }

        $supporter = intval($request->post('supporter'));
        if ($supporter !== 0 && $supporter !== 1) {
            return self::json(false, null, 'Invalid supporter value.');
        }

        $modifications = json_decode($request->post('modifications'), true) ?? [];
        $invalidMod    = $vehicle->parseModifications($modifications);
        if ($invalidMod !== null) {
            return self::json(false, null, 'Invalid modifications ("' . $invalidMod . '") submitted, please try again.');
        }

        $repair = $request->post('repair');
        $damage = $vehicle->deprecated_damage;

        if ($repair === 'fix') {
            $damage = null;
        } else if ($repair === 'break') {
            $damage = '{';

            // No doors
            $damage .= '"doors":{"1":true,"2":true,"3":true,"4":true,"5":true,"0":true},';
            // Very dirty
            $damage .= '"dirt":15.0,';
            // No tires
            $damage .= '"tires":{"1":true,"2":true,"3":true,"4":true,"5":true,"0":true},';
            // No windows
            $damage .= '"windows":{"1":true,"2":true,"3":true,"4":true,"5":true,"6":true,"7":true,"0":true},';
            // Damage completely fucked
            $damage .= '"tank":0.0,"body":0.0,"general":1000,"engine":0.0';

            $damage .= '}';
        }

        $vehicle->update([
            'owner_cid'                => $character->character_id,
            'plate'                    => $plate,
            'deprecated_modifications' => $vehicle->deprecated_modifications,
            'deprecated_damage'        => $damage,
            'deprecated_fuel'          => $fuel,
            'deprecated_supporter'     => $supporter,
        ]);

        Session::flash('success', 'Vehicle was successfully edited');
        return self::json(true);
    }

    /**
     * Returns basic character info for the map
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getCharacters(Request $request): \Illuminate\Http\Response
    {
        $ids = $request->post('ids', []);
        if (empty($ids) || ! is_array($ids)) {
            return (new \Illuminate\Http\Response([
                'status' => false,
            ], 200))->header('Content-Type', 'application/json');
        }

        $characters = Character::query()->whereIn('character_id', $ids)->select([
            'character_id', 'gender',
        ])->get()->toArray();

        return (new \Illuminate\Http\Response([
            'status' => true,
            'data'   => $characters,
        ], 200))->header('Content-Type', 'application/json');
    }

    public function export(Character $character, Request $request): \Illuminate\Http\Response
    {
        $export = [
            '**' . $character->first_name . ' ' . $character->last_name . '**',
            'DOB: - ' . $character->date_of_birth,
            'CID: - ' . $character->character_id,
            'Phone Number: - ' . $character->phone_number,
            'Gender: - ' . (intval($character->gender) === 1 ? 'Female' : 'Male'),
        ];

        $player = $character->player()->first();

        $discords = [];
        foreach ($player->getIdentifiers() as $identifier) {
            if (Str::startsWith($identifier, 'discord:')) {
                $discords[] = '<@' . str_replace('discord:', '', $identifier) . '>';
            }
        }
        $export[] = 'Email(s): - ' . ($discords ? implode(', ', $discords) : 'N/A');
        $export[] = '';

        // Export Vehicles
        $export[] = '**Vehicles**';

        $vehicles = $character->vehicles()->get();
        foreach ($vehicles as $vehicle) {
            $export[] = $vehicle->model_name . ' - ' . $vehicle->plate . ' - ' . $vehicle->garage();
        }

        if (empty($vehicles)) {
            $export[] = 'N/A';
        }

        $export[] = '';

        // Export Properties
        $export[] = '**Houses**';

        $properties = $character->properties()->get();
        foreach ($properties as $property) {
            $export[] = $property->property_address . ' - ' . $property->companyName();
        }

        if (empty($properties)) {
            $export[] = 'N/A';
        }

        return self::text(200, implode(PHP_EOL, $export));
    }

    public function savingsData(int $id)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_SAVINGS_LOGS)) {
            abort(401);
        }

        if (! $id || $id < 1) {
            return $this->json(false, null, 'Invalid ID');
        }

        $account = DB::table('savings_accounts')
            ->select('id', 'character_id', 'name', 'access')
            ->where('id', '=', $id)
            ->first();

        if (! $account) {
            return $this->json(false, null, 'Invalid ID');
        }

        $access   = json_decode($account->access, true) ?? [];
        $access[] = $account->character_id;

        unset($account->access);

        $access = Character::select(["player_name", DB::raw("CONCAT(first_name, ' ', last_name) as full_name"), "character_id", "characters.license_identifier"])
            ->leftJoin("users", "characters.license_identifier", "=", "users.license_identifier")
            ->whereIn("character_id", $access)
            ->orderBy("full_name")
            ->get()->toArray();

        $logs = DB::table('savings_accounts_logs')
            ->select(DB::raw('characters.license_identifier as license, characters.character_id, CONCAT(first_name, " ", last_name) as name, action, amount, reason, timestamp'))
            ->leftJoin('characters', 'savings_accounts_logs.character_id', '=', 'characters.character_id')
            ->where('account_id', '=', $id)
            ->orderByDesc('timestamp')
            ->get()->toArray();

        return $this->json(true, [
            "account" => $account,
            "access"  => $access,
            "logs"    => $logs,
        ]);
    }

    private function getPedModels()
    {
        $peds = ServerAPI::getPeds();

        $models = [];

        foreach ($peds as $data) {
            $models[] = $data["ModelName"];
        }

        return $models;
    }

}
