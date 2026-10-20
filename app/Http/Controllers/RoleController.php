<?php

namespace App\Http\Controllers;

use App\AuditLog;
use App\Helpers\PermissionHelper;
use App\Http\Resources\PlayerRoleResource;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

        $query->orWhereJsonLength('panel_permissions', '>', 0);

        $players = $query
            ->orderBy('player_name', 'asc')
            ->orderBy('playtime', 'desc')
            ->get();

        $players = $players->unique('license_identifier');

        return Inertia::render('Roles/Index', [
            'players'  => PlayerRoleResource::collection($players),
            'roles'    => self::AllowedRoles,
            'readonly' => !env('ALLOW_ROLE_EDITING', false) || !$this->isSuperAdmin($request),
            'panelPermissions' => PermissionHelper::getFrontendPermissions(),
            'permissionInheritance' => PermissionHelper::getFrontendInheritance(),
            'grantablePermissions' => PermissionHelper::getGrantablePermissions(user()),
            'canEditPermissions' => $this->isSuperAdmin($request),
        ]);
    }

    public function get(Request $request, Player $player)
    {
        return $this->json(true, PlayerRoleResource::make($player));
    }

    public function updatePermissions(Request $request, Player $player)
    {
        if (!$this->isSuperAdmin($request)) {
            return $this->json(false, null, 'You do not have permission to edit panel permissions.');
        }

        $permissions = PermissionHelper::getFrontendPermissions();
        $validator = Validator::make($request->all(), [
            'panel_permissions' => ['present', 'array', 'max:' . count($permissions)],
            'panel_permissions.*' => ['string', 'distinct', Rule::in(array_keys($permissions))],
        ]);

        if ($validator->fails()) {
            return $this->json(false, null, $validator->errors()->first());
        }

        $before = PermissionHelper::getPlayerPermissions($player);
        $after = array_values($validator->validated()['panel_permissions']);
        $added = array_diff($after, $before);
        $removed = array_diff($before, $after);
        $grantable = PermissionHelper::getGrantablePermissions(user());

        if (array_diff($added, $grantable)) {
            return $this->json(false, null, 'You do not have permission to grant one or more of these permissions.');
        }

        foreach ($removed as $permission) {
            if (($permissions[$permission] ?? 0) === PermissionHelper::LEVEL_ROOT && !$this->isRoot($request)) {
                return $this->json(false, null, 'Only root users can edit root-only permission grants.');
            }
        }

        if (!$added && !$removed) {
            return $this->json(true);
        }

        sort($after);
        $player->panel_permissions = $after ?: null;
        $player->save();

        AuditLog::log(license(), 'player.panel_permissions', 'player', $player->license_identifier, sprintf('%s edited the panel permissions of %s.', user()->consoleName(), $player->consoleName()), [
            'before' => $before,
            'after' => $after,
        ]);

        return $this->json(true);
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

        $before = [];
        foreach (array_keys($updates) as $role) {
            $before[$role] = $player->{$role};
        }

        $player->update($updates);

        $user = user();

        AuditLog::log(license(), 'role.edit', 'player', $player->license_identifier, sprintf('%s edited the role flags of %s.', $user->consoleName(), $player->consoleName()), [
            'player'  => $player->license_identifier,
            'before'  => $before,
            'after'   => $updates,
        ]);

        // TODO
        /*
        $license = $player->license_identifier;
        $status = StatusHelper::get($license);

        if ($status) {
            ServerAPI::refreshUser($status['server'], $license);
        }
        */

        return $this->json(true, PlayerRoleResource::make($player));
    }
}
