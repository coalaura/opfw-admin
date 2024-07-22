<?php

namespace App\Http\Controllers;

use App\Helpers\LoggingHelper;
use App\Helpers\OPFWHelper;
use App\Http\Resources\InventoryLogResource;
use App\Http\Resources\LogResource;
use App\Log;
use App\Player;
use App\Server;
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
        "wallet"      => 10,
        "folder"      => 80,
        "archive"     => 300,
        "container"   => 2000,
        "ender_chest" => 500,
        "shared"      => 100,
        "trunk"       => 10,
    ];

    /**
     * Display a certain inventory.
     *
     * @param string $inventory
     * @param Request $request
     */
    public function show(string $inventory, Request $request)
    {
        if (Str::contains($inventory, ':')) {
            $inventory = explode(':', $inventory)[0];

            return redirect('/inventory/' . $inventory);
        }

        $inventoryParams = explode('-', $inventory);

        if (sizeof($inventoryParams) < 2) {
            abort(400);
        }

        $itemList = $this->itemList();
        $slots    = self::MinInventorySlots[$inventoryParams[0]] ?? 5;
        $contents = [];

        if ($this->isSuperAdmin($request)) {
            $items = DB::table('inventories')->where('inventory_name', '=', $inventory)->get();

            foreach ($items as $item) {
                $id       = $item->id;
                $itemName = $item->item_name;
                $metadata = json_decode($item->item_metadata, true) ?? [];
                $slot     = $item->inventory_slot;

                if (!isset($contents[$slot])) {
                    $contents[$slot] = [];
                }

                $contents[$slot][] = [
                    'id'       => $id,
                    'name'     => $itemName,
                    'metadata' => $metadata,
                ];

                if ($slot > $slots) {
                    $slots = $slot;
                }
            }

            for ($slot = 1; $slot <= $slots; $slot++) {
                if (!isset($contents[$slot])) {
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

        if (!$id || $id <= 0) {
            abort(404);
        }

        $query = DB::table('inventories')
            ->select('inventory_name');

        switch ($type) {
            case "trunk": // trunk-class-id
                $query->where(DB::raw("SUBSTRING_INDEX(inventory_name, '-', 1)"), '=', $type)
                    ->where(DB::raw("SUBSTRING_INDEX(inventory_name, '-', -1)"), '=', $id);

                break;

            case "property": // property-id-storage
                $query->where(DB::raw("SUBSTRING_INDEX(inventory_name, '-', 2)"), '=', $type . '-' . $id);

                break;

            default:
                abort(404);
        }

        $item = $query->first();

        if (!$item) {
            abort(404);
        }

        return redirect('/inventory/' . $item->inventory_name);
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
            ->where('action', '=', 'Item Moved')
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
     * Updates all items in a certain inventory slot.
     *
     * @param string $inventory
     * @param int $slot
     * @param Request $request
     * @return Response
     */
    public function update(string $inventory, int $slot, Request $request)
    {
        if (!$this->isSuperAdmin($request)) {
            abort(403);
        }

        $name     = strtolower(trim($request->input('name')));
        $amount   = intval($request->input('amount'));
        $metadata = $request->input('metadata');

        if (!$this->isValidItem($name) || !$amount || $amount <= 0 || $amount > 255 || !json_decode($metadata)) {
            abort(400);
        }

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

        LoggingHelper::log(consoleName() . ' changed all items in '. $inventory .' (slot ' . $slot . ') to ' . $amount . 'x ' . $name . ' (' . json_encode($metadata) . ').');

        DB::table('inventories')->where('inventory_name', '=', $inventory)->where('inventory_slot', '=', $slot)->update([
            'item_name'     => $name,
            'item_metadata' => $metadata,
        ]);

        $this->refresh($inventory);

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
        if (!$this->isSuperAdmin($request)) {
            abort(403);
        }

        LoggingHelper::log(consoleName() . ' deleted all items in '. $inventory .' (slot ' . $slot . ').');

        DB::table('inventories')->where('inventory_name', '=', $inventory)->where('inventory_slot', '=', $slot)->delete();

        $this->refresh($inventory);

        return redirect('/inventory/' . $inventory);
    }

    private function refresh(string $inventory)
    {
        $serverIp = Server::getFirstServer();

        if (!$serverIp) {
            return;
        }

        $response = OPFWHelper::refreshInventory($serverIp, $inventory);

        return $response->status;
    }

    private function isValidItem(string $name)
    {
        $list = $this->itemList();

        return isset($list[$name]);
    }

    private function itemList()
    {
        $serverIp = Server::getFirstServer();

        if (!$serverIp) {
            return [];
        }

        return OPFWHelper::getItemsJSON($serverIp) ?? [];
    }
}
