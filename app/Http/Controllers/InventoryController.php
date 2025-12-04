<?php
namespace App\Http\Controllers;

use App\Character;
use App\Helpers\LoggingHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\ServerAPI;
use App\Http\Resources\LogResource;
use App\Log;
use App\Player;
use App\Server;
use App\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    const MinInventorySlots = [
        "character"   => 25,
        "ground"      => 50,
        "public"      => 10,
        "glovebox"    => 5,
        "motel"       => 65,
        "property"    => 1000,
        "evidence"    => 500,
        "locker"      => 150,
        "airdrop"     => 100,
        "crate"       => 100,
        "ped"         => 5,
        "letterbox"   => 4,
        "backpack"    => 15,
        "wallet"      => 15,
        "folder"      => 80,
        "archive"     => 300,
        "container"   => 2000,
        "ender_chest" => 500,
        "shared"      => 100,
        "trunk"       => 10,
    ];

    const AttachableJobs = [
        "Law Enforcement",
        "Medical",
        "Government",
    ];

    const StringMetadataKeys = [
        "nameOverride",
        "firstName",
        "lastName",
    ];

    /**
     * Display a certain inventory.
     *
     * @param string $inventory
     */
    public function show(string $inventory)
    {
        if (Str::contains($inventory, ':')) {
            $inventory = explode(':', $inventory)[0];

            return redirect('/inventory/' . $inventory);
        }

        $inventoryParams = explode('-', $inventory);

        if (sizeof($inventoryParams) < 2) {
            abort(400);
        }

        $itemList = ServerAPI::getItems(true);
        $slots    = self::MinInventorySlots[$inventoryParams[0]] ?? 5;
        $contents = [];

        if (PermissionHelper::hasPermission(PermissionHelper::PERM_VIEW_INVENTORY)) {
            $items = DB::table('inventories')->where('inventory_name', '=', $inventory)->get();

            foreach ($items as $item) {
                $id       = $item->id;
                $itemName = $item->item_name;
                $metadata = json_decode($item->item_metadata, true) ?? [];
                $slot     = $item->inventory_slot;

                if (! isset($contents[$slot])) {
                    $contents[$slot] = [];
                }

                $contents[$slot][] = [
                    'id'         => $id,
                    'name'       => $itemName,
                    'metadata'   => $metadata,
                    'durability' => $this->calculateDurability($itemName, $metadata, $itemList),
                ];

                if ($slot > $slots) {
                    $slots = $slot;
                }
            }

            for ($slot = 1; $slot <= $slots; $slot++) {
                if (! isset($contents[$slot])) {
                    $contents[$slot] = [];
                }
            }
        }

        return Inertia::render('Inventories/Show', [
            'name'     => $inventory,
            'contents' => $contents,
            'items'    => $itemList,
        ]);
    }

    /**
     * Resolves an incomplete inventory.
     *
     * @param string $inventory
     */
    public function resolve(string $inventory)
    {
        // Remove slot id
        if (Str::contains($inventory, ':')) {
            $inventory = explode(':', $inventory)[0];
        }

        $inventoryParams = explode('-', $inventory);

        if (sizeof($inventoryParams) !== 2) {
            abort(404);
        }

        $type = $inventoryParams[0];
        $id   = intval($inventoryParams[1]);

        if (! $id || $id <= 0) {
            abort(404);
        }

        $query = DB::table('inventories')
            ->select('inventory_name');

        switch ($type) {
            case "property": // property-id-storage
                $query->where(DB::raw("SUBSTRING_INDEX(inventory_name, '-', 2)"), '=', $type . '-' . $id);

                break;

            default:
                abort(404);
        }

        $item = $query->first();

        if (! $item) {
            abort(404);
        }

        return redirect('/inventory/' . $item->inventory_name);
    }

    /**
     * Resolves a trunk inventory.
     *
     * @param Vehicle $vehicle
     */
    public function resolveTrunk(Vehicle $vehicle)
    {
        $class = $vehicle->getVehicleClass();

        if (! $class) {
            abort(404);
        }

        return redirect('/inventory/trunk-' . $class . '-' . $vehicle->vehicle_id);
    }

    /**
     * Show inventory logs for a certain inventory.
     *
     * @param string $inventory
     * @param Request $request
     */
    public function logs(string $inventory, Request $request)
    {
        if (Str::contains($inventory, ':')) {
            $inventory = explode(':', $inventory)[0];

            return redirect('/inventory/logs/' . $inventory);
        }

        $query = Log::query()
            ->select(['id', 'identifier', 'action', 'details', 'metadata', 'timestamp'])
            ->whereIn('action', ['Item Moved', 'Item Given'])
            ->where(function ($subQuery) use ($inventory) {
                $subQuery->where(DB::raw("JSON_EXTRACT(metadata, '$.startInventory')"), '=', $inventory)
                    ->orWhere(DB::raw("JSON_EXTRACT(metadata, '$.endInventory')"), '=', $inventory);
            })
            ->orderByDesc('timestamp');

        $page = Paginator::resolveCurrentPage('page');

        $query->limit(30)->offset(($page - 1) * 30);

        $logs = LogResource::collection($query->get());

        return Inertia::render('Inventories/Logs', [
            'name'      => $inventory,
            'logs'      => LogResource::collection($logs),
            'links'     => $this->getPageUrls($page),
            'playerMap' => Player::fetchLicensePlayerNameMap($logs->toArray($request), 'licenseIdentifier'),
            'page'      => $page,
        ]);
    }

    /**
     * Show inventory logs for a certain item.
     *
     * @param string $inventory
     * @param Request $request
     */
    public function itemHistory(int $itemId, Request $request)
    {
        $query = Log::query()
            ->select(['id', 'identifier', 'action', 'details', 'metadata', 'timestamp'])
            ->whereIn('action', ['Item Moved', 'Item Given'])
            ->where(DB::raw("JSON_CONTAINS(metadata, $itemId, '$.itemIds')"), '=', '1')
            ->orderByDesc('timestamp');

        $page = Paginator::resolveCurrentPage('page');

        $query->limit(30)->offset(($page - 1) * 30);

        $logs = LogResource::collection($query->get());

        return Inertia::render('Inventories/Logs', [
            'name'      => 'item ' . $itemId,
            'logs'      => LogResource::collection($logs),
            'links'     => $this->getPageUrls($page),
            'playerMap' => Player::fetchLicensePlayerNameMap($logs->toArray($request), 'licenseIdentifier'),
            'page'      => $page,
        ]);
    }

    /**
     * Updates all items in a certain inventory slot.
     *
     * @param string $inventory
     * @param int $slot
     * @param Request $request
     * @return Response
     */
    public function update(string $inventory, int $slot, Request $request)
    {
        if (! $this->isSuperAdmin($request)) {
            abort(403);
        }

        $name     = strtolower(trim($request->input('name')));
        $amount   = intval($request->input('amount'));
        $metadata = $request->input('metadata');

        if (! $this->isValidItem($name) || ! $amount || $amount <= 0 || $amount > 255) {
            abort(400);
        }

        $decoded = json_decode($metadata, true);

        if (! is_array($decoded)) {
            abort(400);
        }

        if (! $decoded) {
            $decoded = [];
        }

        foreach ($decoded as $key => $value) {
            if ($value === false || $value === null) {
                unset($decoded[$key]);
            } else if (in_array($key, self::StringMetadataKeys)) {
                $decoded[$key] = strval($value);
            }
        }

        $metadata = json_encode($decoded);

        $items = DB::table('inventories')
            ->select('id')
            ->where('inventory_name', '=', $inventory)
            ->where('inventory_slot', '=', $slot)
            ->get()->toArray();

        $diff = $amount - sizeof($items);

        if ($diff > 0) {
            $insert = [];

            for ($i = 0; $i < $diff; $i++) {
                $insert[] = [
                    'inventory_name' => $inventory,
                    'inventory_slot' => $slot,
                    'item_name'      => $name,
                    'item_metadata'  => $metadata,
                ];
            }

            DB::table('inventories')->insert($insert);
        } else if ($diff < 0) {
            $delete = [];

            for ($i = 0; $i < -$diff; $i++) {
                $delete[] = $items[$i]->id;
            }

            DB::table('inventories')->whereIn('id', $delete)->delete();
        }

        LoggingHelper::log(consoleName() . ' changed all items in ' . $inventory . ' (slot ' . $slot . ') to ' . $amount . 'x ' . $name . ' (' . $metadata . ').');

        DB::table('inventories')->where('inventory_name', '=', $inventory)->where('inventory_slot', '=', $slot)->update([
            'item_name'     => $name,
            'item_metadata' => $metadata,
        ]);

        $this->refresh($inventory);

        return redirect('/inventory/' . $inventory);
    }

    /**
     * Moves all items in a certain inventory slot.
     *
     * @param string $inventory
     * @param int $slot
     * @param Request $request
     * @return Response
     */
    public function move(string $inventory, int $slot, Request $request)
    {
        if (! $this->isSuperAdmin($request)) {
            abort(403);
        }

        $target = strtolower(trim($request->input('target')));

        if (! $target || ! preg_match('/^\w+-.+/m', $target)) {
            abort(400);
        }

        $usedSlots = DB::table('inventories')
            ->select('inventory_slot')
            ->where('inventory_name', '=', $target)
            ->groupBy('inventory_slot')
            ->orderBy('inventory_slot')
            ->get()->toArray();

        $usedSlots = array_values(array_map(function ($slot) {
            return $slot->inventory_slot;
        }, $usedSlots));

        $availableSlot = 1;

        while (in_array($availableSlot, $usedSlots)) {
            $availableSlot++;
        }

        LoggingHelper::log(consoleName() . ' moved all items in ' . $inventory . ' (slot ' . $slot . ') to ' . $target . ' (slot ' . $availableSlot . ').');

        DB::table('inventories')->where('inventory_name', '=', $inventory)->where('inventory_slot', '=', $slot)->update([
            'inventory_name' => $target,
            'inventory_slot' => $availableSlot,
        ]);

        $this->refresh($inventory);
        $this->refresh($target);

        return redirect('/inventory/' . $inventory);
    }

    /**
     * Deletes all items from a certain inventory slot.
     *
     * @param string $inventory
     * @param int $slot
     * @param Request $request
     * @return Response
     */
    public function delete(string $inventory, int $slot, Request $request)
    {
        if (! $this->isSuperAdmin($request)) {
            abort(403);
        }

        LoggingHelper::log(consoleName() . ' deleted all items in ' . $inventory . ' (slot ' . $slot . ').');

        DB::table('inventories')->where('inventory_name', '=', $inventory)->where('inventory_slot', '=', $slot)->delete();

        $this->refresh($inventory);

        return redirect('/inventory/' . $inventory);
    }

    public function attachIdentity(Request $request, Character $character)
    {
        if (! $this->isSuperAdmin($request)) {
            abort(403);
        }

        $metadata = [
            'characterId' => $character->character_id,
            'firstName'   => $character->first_name,
            'lastName'    => $character->last_name,
            'gender'      => $character->gender,
            'dateOfBirth' => $character->date_of_birth,
        ];

        $jobName = $character->job_name;

        if ($jobName && in_array($jobName, self::AttachableJobs)) {
            $metadata['jobName'] = $jobName;
        }

        return $this->json(true, $metadata);
    }

    private function refresh(string $inventory)
    {
        $serverUrl = Server::getFirstServer("url");

        if (! $serverUrl) {
            return;
        }

        $response = OPFWHelper::refreshInventory($serverUrl, $inventory);

        return $response->status;
    }

    private function isValidItem(string $name)
    {
        $list = ServerAPI::getItems();

        return isset($list[$name]);
    }

    private function calculateDurability(string $itemName, ?array $itemMetadata, array $itemList)
    {
        $itemData = $itemList[$itemName] ?? null;

        if (! $itemData || ($itemMetadata && ! empty($itemMetadata['noDurability']))) {
            return false;
        }

        if ($itemMetadata && isset($itemMetadata['durabilityPercent'])) {
            return min(100, max(0, $itemMetadata['durabilityPercent']));
        }

        $degradeAfterTime = ! empty($itemData['degradeAfter']) ? $itemData['degradeAfter']['time'] : null;

        if (! $degradeAfterTime) {
            return false;
        }

        $timestamp = round(time());

        $degradesAt = ($itemMetadata && isset($itemMetadata['degradesAt'])) ? $itemMetadata['degradesAt'] : null;

        if (! $degradesAt || ! is_int($degradesAt)) {
            return 100;
        }

        if ($timestamp >= $degradesAt) {
            return 0;
        }

        $degradationStartsAt = $degradesAt - $degradeAfterTime;

        if ($timestamp <= $degradationStartsAt) {
            return 100;
        }

        $timeElapsed = $timestamp - $degradationStartsAt;
        $durabilityPercent = 100 - (($timeElapsed / $degradeAfterTime) * 100);

        return round(min(100, max(0, $durabilityPercent)), 2);
    }
}
