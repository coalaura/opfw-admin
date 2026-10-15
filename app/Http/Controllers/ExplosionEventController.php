<?php

namespace App\Http\Controllers;

use App\ExplosionEvent;
use App\Helpers\ExplosionTypeHelper;
use App\Helpers\PermissionHelper;
use App\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ExplosionEventController extends Controller
{
    public function index(Request $request)
    {
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_EXPLOSION_EVENTS)) {
            abort(401);
        }

        $validator = Validator::make($request->only([
            'license',
            'explosion_type',
            'category',
            'canceled',
            'minigame',
            'weapon_hash',
            'no_damage',
            'min_distance',
            'max_distance',
            'after',
            'before',
            'page',
        ]), [
            'license' => 'nullable|string|max:50',
            'explosion_type' => 'nullable|integer',
            'category' => 'nullable|string|in:ignorable,common,dangerous,other,hide_ignorable,hide_common',
            'canceled' => 'nullable|boolean',
            'minigame' => 'nullable|string|in:none,arena,battle_royale,zombie_pill,training',
            'weapon_hash' => 'nullable|integer',
            'no_damage' => 'nullable|boolean',
            'min_distance' => 'nullable|numeric|min:0',
            'max_distance' => 'nullable|numeric|min:0',
            'after' => 'nullable|integer|min:0',
            'before' => 'nullable|integer|min:0',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            $filters = $validator->getData();
            unset($filters['page']);

            return Inertia::render('Logs/ExplosionEvents', [
                'logs' => [],
                'filters' => (object) $filters,
                'errors' => (object) $validator->errors()->messages(),
                'links' => ['prev' => null, 'next' => null],
                'page' => 1,
                'playerMap' => (object) [],
                'explosionTypes' => (object) ExplosionTypeHelper::frontendTypes(),
            ]);
        }

        $filters = $validator->validated();
        unset($filters['page']);

        $query = ExplosionEvent::query()->category($filters['category'] ?? '')->orderByDesc('timestamp')->orderByDesc('id');

        $columns = [
            'license' => 'license_identifier',
            'explosion_type' => 'explosion_type',
            'canceled' => 'canceled',
            'weapon_hash' => 'weapon_hash',
            'no_damage' => 'no_damage',
        ];

        foreach ($columns as $filter => $column) {
            if ($request->filled($filter)) {
                $query->where($column, $filters[$filter]);
            }
        }

        if (($filters['minigame'] ?? '') === 'none') {
            $query->where(function ($query) {
                $query->whereNull('minigame')->orWhere('minigame', '');
            });
        } elseif ($request->filled('minigame')) {
            $query->where('minigame', $filters['minigame']);
        }

        if ($request->filled('min_distance')) {
            $query->where('distance', '>=', $filters['min_distance']);
        }

        if ($request->filled('max_distance')) {
            $query->where('distance', '<=', $filters['max_distance']);
        }

        if ($request->filled('after')) {
            $query->where('timestamp', '>=', $filters['after']);
        }

        if ($request->filled('before')) {
            $query->where('timestamp', '<=', $filters['before']);
        }

        $page = $query->simplePaginate(25)->appends($filters);
        $logs = $page->items();

        return Inertia::render('Logs/ExplosionEvents', [
            'logs' => $logs,
            'filters' => (object) $filters,
            'errors' => (object) [],
            'links' => [
                'prev' => $page->previousPageUrl(),
                'next' => $page->nextPageUrl(),
            ],
            'page' => $page->currentPage(),
            'playerMap' => Player::fetchLicensePlayerNameMap($logs, 'license_identifier'),
            'explosionTypes' => (object) ExplosionTypeHelper::frontendTypes(),
        ]);
    }
}
