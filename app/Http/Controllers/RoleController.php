<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlayerRoleResource;
use App\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    const AllowedRoles = [
        // Editable by super-admins
        'is_trusted'      => false,
        'is_staff'        => false,
        'is_senior_staff' => false,

        // Editable only by root
        'is_debugger'     => true,
        'is_super_admin'  => true,
    ];

    public function index(Request $request)
    {
        $query = Player::query();

        foreach (self::AllowedRoles as $role => $_) {
            $query->orWhere($role, '=', 1);
        }

        $players = $query
            ->orderBy('player_name', 'asc')
            ->orderBy('playtime', 'desc')
            ->get();

        $players = $players->unique('license_identifier');

        return Inertia::render('Roles/Index', [
            'players'  => PlayerRoleResource::collection($players),
            'roles'    => self::AllowedRoles,
            'readonly' => !env('ALLOW_ROLE_EDITING', false) || !$this->isSuperAdmin($request),
        ]);
    }

    public function get(Request $request, Player $player)
    {
        return $this->json(true, PlayerRoleResource::make($player));
    }

    public function update(Request $request, Player $player)
    {
        if (!env('ALLOW_ROLE_EDITING', false) || !$this->isSuperAdmin($request)) {
            return $this->json(false, null, 'You do not have permission for this.');
        }

        $updates = [];

        foreach (self::AllowedRoles as $role => $restricted) {
            if (!$request->has($role)) {
                continue;
            }

            $value = $request->get($role);

            if ($value === "false" || $value === "" || $value === "0" || $value === 0) {
                $value = false;
            }

            if ($restricted && !$this->isRoot($request)) {
                return $this->json(false, null, 'You do not have permission to edit this role.');
            }

            $updates[$role] = $value ? 1 : 0;
        }

        if (empty($updates)) {
            return $this->json(true);
        }

        $player->update($updates);

        return $this->json(true);
    }
}
