<?php
namespace App\Http\Controllers;

use App\Ban;
use App\Helpers\DeviceHelper;
use App\Helpers\DiscordAttachmentHelper;
use App\Helpers\GeneralHelper;
use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Http\Requests\BanStoreRequest;
use App\Http\Requests\BanUpdateRequest;
use App\Http\Resources\BanResource;
use App\Http\Resources\PlayerResource;
use App\PanelLog;
use App\Player;
use App\Warning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlayerBanController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->bans($request, false, false);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexMine(Request $request): Response
    {
        return $this->bans($request, true, false);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexSystem(Request $request): Response
    {
        return $this->bans($request, false, true);
    }

    public function findUserBanHash(string $hash)
    {
        $ban = Ban::query()
            ->leftJoin('users', 'identifier', '=', 'license_identifier')
            ->where('ban_hash', $hash)
            ->whereNotNull('license_identifier')
            ->first();

        if (! $ban) {
            abort(404);
        }

        return redirect('/players/' . $ban->license_identifier);
    }

    public function banInfo(string $hash)
    {
        $ban = Ban::query()
            ->leftJoin('users', 'identifier', '=', 'license_identifier')
            ->where('ban_hash', $hash)
            ->whereNotNull('license_identifier')
            ->first();

        if (! $ban) {
            return $this->json(true, null, "Not found");
        }

        $creator = Player::query()
            ->where('license_identifier', $ban->creator_identifier)
            ->first();

        $note = $creator ? Warning::query()
            ->where('issuer_id', $creator->user_id)
            ->where('player_id', $ban->user_id)
            ->where('warning_type', Warning::TypeNote)
            ->where('can_be_deleted', '=', 1)
            ->orderBy('created_at', 'desc')
            ->first() : null;

        $data = [
            "player"  => $ban->player_name,
            "creator" => $creator ? $creator->player_name : $ban->creator_name,
            "reason"  => $ban->reason,
            "date"    => $ban->timestamp->format('jS \\of F Y'),

            "note"    => $note ? $note->message : false,

            "url"     => "/players/{$ban->license_identifier}",
        ];

        return $this->json(true, $data);
    }

    private function bans(Request $request, bool $showMine, bool $showSystem): Response
    {
        $query = Player::query();

        $query->select([
            'license_identifier',
            'player_name',
            'reason',
            'timestamp',
            'expire',
            'creator_name',
            'creator_identifier',
            'media_devices',
        ]);

        // Filtering by ban hash.
        $this->searchQuery($request, $query, 'banHash', 'ban_hash');

        // Filtering by reason.
        $this->searchQuery($request, $query, 'reason', 'reason');

        // Filtering by creator.
        if ($creator = $request->input('creator')) {
            $query->where('creator_identifier', $creator);
        }

        // Filtering by locked.
        if ($locked = $request->input('locked')) {
            $query->where('locked', '=', $locked === 'yes' ? 1 : 0);
        }

        $query->leftJoin('user_bans', 'identifier', '=', 'license_identifier');

        if ($showMine) {
            $player = user();

            $alias = is_array($player->player_aliases) ? $player->player_aliases : json_decode($player->player_aliases, true);

            $query->where(function ($query) use ($player, $alias) {
                $query->orWhere('creator_identifier', '=', $player->license_identifier);
                $query->orWhereIn('creator_name', $alias);
            });
        }

        if ($showSystem) {
            $query->whereNull('creator_name');
        } else {
            $query->whereNotNull('creator_name');
        }

        $query
            ->whereNotNull('reason')
            ->orderByDesc('timestamp');

        $page = Paginator::resolveCurrentPage('page');
        $query->limit(15)->offset(($page - 1) * 15);

        $players = array_map(function($player) {
            $mediaDevices = !empty($player['media_devices']) ? $player['media_devices'] : false;

            $player['suspicious'] = $mediaDevices && is_array($mediaDevices) ? DeviceHelper::check($mediaDevices): false;

            return $player;
        }, $query->get()->toArray());

        $staff = GeneralHelper::getAllStaff();

        return Inertia::render('Players/Bans', [
            'players' => $players,
            'staff'   => $staff,
            'links'   => $this->getPageUrls($page),
            'page'    => $page,
            'filters' => [
                'banHash' => $request->input('banHash'),
                'reason'  => $request->input('reason'),
                'creator' => $request->input('creator'),
                'locked'  => $request->input('locked'),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Player $player
     * @param BanStoreRequest $request
     * @return RedirectResponse
     */
    public function store(Player $player, BanStoreRequest $request): RedirectResponse
    {
        if ($player->isBanned()) {
            return backWith('error', 'Player is already banned');
        }

        $data = $request->validated();

        if ($data['expire'] && $data['expire'] < 60 * 60) {
            return backWith('error', 'Bans must be at least 1 hour long.');
        }

        // Create a unique hash to go with this player's batch of bans.
        $user = user();
        $hash = Ban::generateHash();

        // Create ban.
        $ban = [
            'reason'             => $data['reason'],
            'expire'             => $data['expire'],
            'ban_hash'           => $hash,
            'creator_name'       => $user->player_name,
            'creator_identifier' => $user->license_identifier,
        ];

        // Get identifiers to ban.
        $identifiers = $player->getBannableIdentifiers();

        // Go through the player's identifiers and create a ban record for each of them.
        foreach ($identifiers as $identifier) {
            $b               = $ban;
            $b['identifier'] = $identifier;

            $player->bans()->updateOrCreate($b);
        }

        // Create reason.
        $reason = $request->input('reason')
        ? 'I banned this person with the reason: `' . $request->input('reason') . '`'
        : 'I banned this person without a reason';

        $reason .= ($ban['expire'] ? ' for ' . GeneralHelper::formatSeconds(intval($ban['expire'])) : ' indefinitely') . '.';

        // Automatically log the ban as a warning.
        $player->warnings()->create([
            'issuer_id'      => $user->user_id,
            'message'        => $reason . ' This warning was generated automatically as a result of banning someone.',
            'can_be_deleted' => 0,
        ]);

        $note = trim($data['note'] ?? '');

        if (! empty($note)) {
            $warning = $player->warnings()->create([
                'issuer_id' => $user->user_id,
                'message'   => $note,
            ]);

            if ($warning) {
                DiscordAttachmentHelper::ensureMessageAttachments($warning);
            }
        }

        $staffName = $user->player_name;

        if (env('HIDE_BAN_CREATOR')) {
            $staffName = "a staff member";
        }

        $kickReason = $request->input('reason')
        ? 'You have been banned by ' . $staffName . ' for reason `' . $request->input('reason') . '`.'
        : 'You have been banned without a specified reason by ' . $staffName;

        $response = OPFWHelper::kickPlayer($user->player_name, $player, $kickReason);

        if (! $player->isStaff()) {
            user()->trackStatistics('banned-player');
        }

        return backWith('success', 'The player has successfully been banned. ' . ($response->status ? 'They have been kicked from the server.' : 'Unable to kick them from the server.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Player $player
     * @param Ban $ban
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Player $player, Ban $ban, Request $request): RedirectResponse
    {
        if ($ban->locked && ! PermissionHelper::hasPermission(PermissionHelper::PERM_LOCK_BAN)) {
            abort(401);
        }

        $user = user();

        // Delete ban
        Ban::query()
            ->where('ban_hash', $ban->ban_hash)
            ->whereNotNull('ban_hash')
            ->delete();

        // Delete linked bans
        Ban::query()
            ->where('smurf_account', $ban->ban_hash)
            ->whereNotNull('smurf_account')
            ->delete();

        PanelLog::log(
            $user->license_identifier,
            "Removed Ban",
            sprintf("%s removed a ban from %s.", $user->consoleName(), $player->consoleName()),
            ['hash' => $ban->ban_hash]
        );

        // Automatically log the ban update as a warning.
        $player->warnings()->create([
            'issuer_id' => $user->user_id,
            'message'   => 'I removed this players ban.' . ($ban->isSystem() ? ' (System ban)' : ''),
        ]);

        return backWith('success', 'The player has successfully been unbanned.');
    }

    public function lockBan(Player $player, Ban $ban, Request $request): RedirectResponse
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_LOCK_BAN)) {
            abort(401);
        }

        Ban::query()->where('ban_hash', '=', $ban->ban_hash)->update([
            'locked' => 1,
        ]);

        return backWith('success', 'The ban has been successfully locked.');
    }

    public function unlockBan(Player $player, Ban $ban, Request $request): RedirectResponse
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_LOCK_BAN)) {
            abort(401);
        }

        Ban::query()->where('ban_hash', '=', $ban->ban_hash)->update([
            'locked' => 0,
        ]);

        return backWith('success', 'The ban has been successfully unlocked.');
    }

    public function schedule(Player $player, Ban $ban, Request $request): RedirectResponse
    {
        $time = intval($request->input('timestamp'));

        if (! $time || $time < time()) {
            return backWith('error', 'Invalid date.');
        }

        if (! $ban->ban_hash) {
            return backWith('error', 'Invalid ban.');
        }

        $count = sizeof($player->uniqueBans());

        if ($count > 1) {
            return backWith('error', 'Cannot schedule ban. Player has multiple active bans.');
        }

        Ban::query()->where('ban_hash', '=', $ban->ban_hash)->update([
            'scheduled_unban' => $time,
        ]);

        // Sometimes there are bans where just a few identifiers are banned not actually the license.
        $isMainLicenseBanned = Ban::query()->where('ban_hash', '=', $ban->ban_hash)->where('identifier', '=', $player->license_identifier)->exists();

        if (! $isMainLicenseBanned) {
            $newBan = $ban->toArray();

            unset($newBan['id']);

            $newBan['identifier']      = $player->license_identifier;
            $newBan['scheduled_unban'] = $time;

            // Add the main license to the ban list, "fixing" the ban.
            $player->bans()->create($newBan);
        }

        $user = user();

        // Automatically log the ban update as a warning.
        $player->warnings()->create([
            'issuer_id' => $user->user_id,
            'message'   => 'I scheduled the removal of this players ban for ' . gmdate('m/d/Y', $time) . '.',
        ]);

        return backWith('success', 'The ban has been successfully scheduled for removal.');
    }

    public function unschedule(Player $player, Ban $ban, Request $request): RedirectResponse
    {
        $ban->update([
            'scheduled_unban' => null,
        ]);

        return backWith('success', 'The ban has been successfully unscheduled.');
    }

    public function unlinkHWID(Player $player, Player $player2, Request $request): RedirectResponse
    {
        if (! $this->isSuperAdmin($request)) {
            abort(401);
        }

        $user = user();

        $tokens  = $player->getTokens();
        $tokens2 = $player2->getTokens();

        if (empty(array_intersect($tokens, $tokens2))) {
            return backWith('error', 'Players are not linked.');
        }

        $newTokens  = array_values(array_diff($tokens, $tokens2));
        $newTokens2 = array_values(array_diff($tokens2, $tokens));

        $player->update([
            'player_tokens' => $newTokens,
        ]);

        $player2->update([
            'player_tokens' => $newTokens2,
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Unlinked HWID",
            sprintf("%s unlinked %s from %s.", $user->consoleName(), $player->consoleName(), $player2->consoleName())
        );

        return backWith('success', 'The players have been successfully unlinked.');
    }

    public function unlinkIdentifiers(Player $player, Player $player2, Request $request): RedirectResponse
    {
        if (! $this->isSuperAdmin($request)) {
            abort(401);
        }

        $user = user();

        $identifiers  = $player->getIdentifiers();
        $identifiers2 = $player2->getIdentifiers();

        $lastUsed  = $player->getLastUsedIdentifiers(true);
        $lastUsed2 = $player2->getLastUsedIdentifiers(true);

        if (! Player::isLinked($identifiers, $identifiers2)) {
            return backWith('error', 'Players are not linked.');
        }

        $intersect = array_values(array_intersect($identifiers, $identifiers2));

        $newIdentifiers = array_values(array_filter($identifiers, function ($identifier) use ($intersect, $lastUsed) {
            return ! in_array($identifier, $intersect) || in_array($identifier, $lastUsed);
        }));

        $newIdentifiers2 = array_values(array_filter($identifiers2, function ($identifier) use ($intersect, $lastUsed2) {
            return ! in_array($identifier, $intersect) || in_array($identifier, $lastUsed2);
        }));

        // Check if still linked
        if (Player::isLinked($newIdentifiers, $newIdentifiers2)) {
            return backWith('error', 'Unable to unlink players.');
        }

        $player->update([
            'identifiers' => $newIdentifiers,
        ]);

        $player2->update([
            'identifiers' => $newIdentifiers2,
        ]);

        PanelLog::log(
            $user->license_identifier,
            "Unlinked Identifier",
            sprintf("%s unlinked %s from %s.", $user->consoleName(), $player->consoleName(), $player2->consoleName())
        );

        return backWith('success', 'The players have been successfully unlinked.');
    }

    /**
     * Display the specified resource for editing.
     *
     * @param Request $request
     * @param Player $player
     * @param Ban $ban
     * @return Response
     */
    public function edit(Request $request, Player $player, Ban $ban): Response
    {
        if (! $ban->creator_name || ($ban->locked && ! PermissionHelper::hasPermission(PermissionHelper::PERM_LOCK_BAN))) {
            abort(401);
        }

        return Inertia::render('Players/Ban/Edit', [
            'player' => new PlayerResource($player),
            'ban'    => new BanResource($ban),
        ]);
    }

    /**
     * Updates the specified resource.
     *
     * @param Player $player
     * @param Ban $ban
     * @param BanUpdateRequest $request
     * @return RedirectResponse
     */
    public function update(Player $player, Ban $ban, BanUpdateRequest $request): RedirectResponse
    {
        if ($ban->locked && ! PermissionHelper::hasPermission(PermissionHelper::PERM_LOCK_BAN)) {
            abort(401);
        }

        $user   = user();
        $reason = $request->input('reason') ?: 'No reason.';

        $expireBefore = $ban->getExpireTimeInSeconds() ? GeneralHelper::formatSeconds($ban->getExpireTimeInSeconds()) : 'permanent';
        $expireAfter  = $request->input('expire') ? GeneralHelper::formatSeconds(intval($request->input('expire')) + (time() - $ban->getTimestamp())) : 'permanent';

        $before = $ban->getExpireTimeInSeconds() || null;
        $after  = $request->input('expire') ? intval($request->input('expire')) + (time() - $ban->getTimestamp()) : null;

        $message = '';

        if ($before === $after && $reason === $ban->reason) {
            return backWith('error', 'You did not change anything!');
        } else if ($before === $after) {
            $message = 'I changed this bans reason to be "' . $reason . '". ';
        } else if ($reason === $ban->reason) {
            $message = 'I updated this ban to be "' . $expireAfter . '" instead of "' . $expireBefore . '". ';
        } else {
            $message = 'I updated this ban to be "' . $expireAfter . '" instead of "' . $expireBefore . '" and changed the reason to "' . $reason . '". ';
        }

        $bans = Ban::query()->where('ban_hash', '=', $ban->ban_hash)->get();
        foreach ($bans->values() as $b) {
            $b->update($request->validated());
        }

        // Automatically log the ban update as a warning.
        $player->warnings()->create([
            'issuer_id' => $user->user_id,
            'message'   => $message .
            'This warning was generated automatically as a result of updating a ban.',
        ]);

        return backWith('success', 'Ban was successfully updated, redirecting back to player page...');
    }

    public function systemInfo(Player $player, Ban $ban)
    {
        if ($ban->creator_name) {
            return $this->json(false, null, 'Not a system ban.');
        }

        $parts = explode('-', $ban->reason);

        if (sizeof($parts) < 2 || $parts[0] !== 'MODDING') {
            return $this->json(false, null, 'Not a modding ban.');
        }

        $type = strtolower($parts[1]);

        // Some ban reasons are different to the anti cheat type
        switch ($type) {
            case 'spectating':
                $type = 'spectate';
                break;
            case 'weapon_spawn':
                $type = 'illegal_weapon';
                break;
            case 'thermal_nightvision':
                $type = 'thermal_night_vision';
                break;
            case 'freecam':
                $type = 'freecam_detected';
                break;
            case 'bad_entity_spawn':
                $type = 'spawned_object';
                break;
        }

        $time = strtotime('-6 months');

        $total = DB::table('anti_cheat_events')
            ->where('type', '=', $type)
            ->where('anti_cheat_events.license_identifier', '!=', $player->license_identifier)
            ->where('anti_cheat_events.timestamp', '>', $time)
            ->count();

        $counts = DB::table('anti_cheat_events')
            ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(IF(user_bans.ban_hash, user_bans.timestamp, MIN(anti_cheat_events.timestamp))), '%d-%m-%Y') as date, SUM(1) as triggers, ban_hash")
            ->leftJoin('user_bans', 'anti_cheat_events.license_identifier', '=', 'user_bans.identifier')
            ->where('type', '=', $type)
            ->where('anti_cheat_events.timestamp', '>', $time)
            ->where('anti_cheat_events.license_identifier', '!=', $player->license_identifier)
            ->orderBy('anti_cheat_events.timestamp')
            ->groupBy('license_identifier')
            ->get();

        $entries       = [];
        $averages      = [];
        $bannedTotal   = 0;
        $unbannedTotal = 0;

        for ($t = $time; $t <= time(); $t += 86400) {
            $date = date('d-m-Y', $t);

            // banned / unbanned
            $entries[$date] = [0, 0];
        }

        foreach ($counts as $count) {
            $date = $count->date;

            $averages[] = $count->triggers;

            if (! $date) {
                continue;
            }

            if ($count->ban_hash) {
                $bannedTotal += 1;
                $entries[$date][0] += 1;
            } else {
                $unbannedTotal += 1;
                $entries[$date][1] += 1;
            }
        }

        $graph = $this->renderGraph(array_values($entries), '', ["green", "red"]);

        $totalPlayers = $bannedTotal + $unbannedTotal;
        $accuracy     = $totalPlayers > 0 ? ($bannedTotal > 0 ? round(($bannedTotal / $totalPlayers) * 100, 2) : 0) : 'N/A';

        $average = round(array_sum($averages) / sizeof($averages), 1);

        return $this->json(true, [
            'total'    => $total,
            'players'  => $totalPlayers,
            'banned'   => $bannedTotal,
            'unbanned' => $unbannedTotal,
            'accuracy' => $accuracy,
            'average'  => $average,
            'graph'    => $graph,
            'since'    => $time,
            'type'     => $type,
        ]);
    }

    public function smurfBan(string $hash): RedirectResponse
    {
        if (! $hash) {
            abort(404);
        }

        $ban = Ban::query()->where('ban_hash', '=', $hash)->whereRaw("SUBSTRING_INDEX(identifier, ':', 1) = 'license'")->first();

        if (! $ban) {
            abort(404);
        }

        $license = $ban->identifier;

        return redirect("/players/{$license}");
    }

    protected function findPlayer(Request $request, string $license)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_LINKED)) {
            abort(401);
        }

        if (! $license || ! Str::startsWith($license, 'license:')) {
            return false;
        }

        $player = Player::query()->select(['player_name', 'license_identifier', 'player_tokens', 'identifiers', 'last_used_identifiers', 'identifiers', 'media_devices'])->where('license_identifier', '=', $license)->get()->first();

        if (! $player) {
            return false;
        }

        return $player;
    }

    public function linkedIPs(Request $request, string $license): \Illuminate\Http\Response
    {
        $player = $this->findPlayer($request, $license);

        if (! $player) {
            return $this->text(404, "Player not found.");
        }

        $ips = $player->getIps();

        if (empty($ips)) {
            return $this->text(404, "No ips found.");
        }

        $badIps = [];

        $ips = array_filter($ips, function ($ip) use (&$badIps) {
            $info = GeneralHelper::ipInfo($ip);

            if ($info) {
                if (in_array($info['isp'], ['OVH SAS'])) {
                    $badIps[] = $info;

                    return false;
                }

                if ($info['proxy']) {
                    $badIps[] = $info;

                    return false;
                }
            }

            return true;
        });

        if (empty($ips)) {
            return $this->text(404, "Only VPN/Proxy IPs found. This means the user has always used a VPN/Proxy when connecting to the server. " . sizeof($badIps) . " bad IPs found.");
        }

        $where = implode(' OR ', array_map(function ($ip) {
            return 'JSON_CONTAINS(identifiers, \'"ip:' . $ip . '"\', \'$\')';
        }, $ips));

        return $this->drawLinked("IPs", $player, $where, $ips);
    }

    public function linkedTokens(Request $request, string $license): \Illuminate\Http\Response
    {
        $player = $this->findPlayer($request, $license);

        if (! $player) {
            return $this->text(404, "Player not found.");
        }

        $tokens = $player->getTokens();

        if (empty($tokens)) {
            return $this->text(404, "No tokens found.");
        }

        $where = "JSON_OVERLAPS(player_tokens, '" . json_encode($player->getTokens()) . "') = 1";

        return $this->drawLinked("Tokens", $player, $where, $tokens);
    }

    public function linkedIdentifiers(Request $request, string $license): \Illuminate\Http\Response
    {
        $player = $this->findPlayer($request, $license);

        if (! $player) {
            return $this->text(404, "Player not found.");
        }

        $identifiers = $player->getBannableIdentifiers();

        if (empty($identifiers)) {
            return $this->text(404, "No identifiers found.");
        }

        $where = "JSON_OVERLAPS(identifiers, '" . json_encode($player->getBannableIdentifiers()) . "') = 1";

        return $this->drawLinked("Identifiers", $player, $where, $identifiers);
    }

    public function linkedDevices(Request $request, string $license): \Illuminate\Http\Response
    {
        $player = $this->findPlayer($request, $license);

        if (! $player) {
            return $this->text(404, "Player not found.");
        }

        $mediaDevices = $player->getComparableMediaDevices();

        if (! $mediaDevices || empty($mediaDevices)) {
            return $this->text(404, "No devices found.");
        }

        $where = "JSON_OVERLAPS(media_devices, '" . json_encode($mediaDevices) . "') = 1";

        return $this->drawLinked('Media Devices', $player, $where, $mediaDevices);
    }

    protected function drawLinked(string $title, Player $player, string $where, array $values)
    {
        $request = request();

        $license = $player->license_identifier;

        $tokens         = $player->getTokens();
        $ips            = $player->getIps();
        $identifiers    = $player->getBannableIdentifiers();
        $gpuMediaDevice = $player->getGPUMediaDevice();
        $mediaDevices   = $player->getComparableMediaDevices();

        $players = Player::query()->select(['player_name', 'license_identifier', 'player_tokens', 'identifiers', 'last_used_identifiers', 'identifiers', 'media_devices', 'last_connection', 'ban_hash', 'playtime'])->leftJoin('user_bans', function ($join) {
            $join->on('license_identifier', '=', 'identifier');
        })->whereRaw($where)->get();

        $raw = [];

        foreach ($players as $found) {
            if ($found->license_identifier !== $license) {
                $foundTokens         = $found->getTokens();
                $foundIps            = $found->getIps();
                $foundIdentifiers    = $found->getBannableIdentifiers();
                $foundGPUMediaDevice = $found->getGPUMediaDevice();
                $foundMediaDevices   = $found->getComparableMediaDevices();

                $matchingDevices = array_intersect($mediaDevices, $foundMediaDevices);

                $devicesOverlap = sizeof($matchingDevices);
                $gpuOverlap     = $gpuMediaDevice && $gpuMediaDevice === $foundGPUMediaDevice;

                $count            = sizeof(array_intersect($tokens, $foundTokens));
                $countIps         = sizeof(array_intersect($ips, $foundIps));
                $countIdentifiers = sizeof(array_intersect($identifiers, $foundIdentifiers));

                $total = $count + $countIps + $countIdentifiers + $devicesOverlap + ($gpuOverlap ? 1 : 0);

                $counts = sprintf(
                    '<span style="color:#ff5b5b">%d</span>/<span style="color:#5bc2ff">%d</span>/<span style="color:#65d54e">%d</span>/<span style="color:#f0c622" title="%s">%d</span>',
                    $count,
                    $countIps,
                    $countIdentifiers,
                    implode("\n", $matchingDevices),
                    $devicesOverlap
                );

                $playtime = "Playtime is about " . GeneralHelper::formatSeconds($found->playtime);
                $webgl    = $gpuOverlap ? sprintf('<span style="color:#8fe17f" title="WebGL fingerprint matches exactly: %s">webgl</span>', $gpuMediaDevice) : '<span style="color:#e17f7f;text-decoration:line-through" title="WebGL fingerprint does not match">webgl</span>';

                $raw[] = [
                    'label'      => sprintf('[%s] %s - %s - <a href="/players/%s" target="_blank" title="%s">%s</a>', $counts, $webgl, GeneralHelper::formatTimestamp($found->last_connection), $found->license_identifier, $playtime, $found->player_name),
                    'connection' => $found->last_connection,
                    'count'      => $total,
                    'banned'     => $found->ban_hash !== null,
                ];
            }
        }

        usort($raw, function ($a, $b) {
            if ($a['count'] === $b['count']) {
                return $a['connection'] < $b['connection'];
            }

            return $a['count'] < $b['count'];
        });

        $linked = [];
        $banned = [];

        foreach ($raw as $item) {
            if ($item['banned']) {
                $banned[] = $item['label'];
            } else {
                $linked[] = $item['label'];
            }
        }

        if (empty($linked)) {
            $linked[] = "<i>None</i>";
        }

        if (empty($banned)) {
            $banned[] = "<i>None</i>";
        }

        $counts = '<span style="color:#ff5b5b">Tokens</span> / <span style="color:#5bc2ff">IPs</span> / <span style="color:#65d54e">Identifiers</span> / <span style="color:#f0c622">Media Devices</span>';

        return $this->fakeText(200, sprintf(
            "Found: <b>%d</b> Accounts for <a href=\"/players/%s\" target=\"_blank\">%s</a> using %s %s\n\n<i style=\"color:#c68dbf\">[%s] - Last Connection - Player Name</i>\n\n<i style=\"color:#a3ff9b\">- Not Banned</i>\n%s\n\n<i style=\"color:#ff8e8e\">- Banned</i>\n%s\n\n%s%s",
            sizeof($raw),
            $license,
            $player->player_name,
            $title,
            sprintf('<a href="?fresh" class="sup" %s>[Fresh]</a>', $request->has('fresh') ? 'id="fresh"' : ''),
            $counts,
            implode("\n", $linked),
            implode("\n", $banned),
            $this->isRoot($request) ? sprintf("<div style=\"color:#b0b0b0;font-style:italic\">- Values\n%s", implode("\n", $values)) : '',
            '<script>(()=>{const a=document.getElementById("fresh");if(a){function b(){return c-=1,a.innerText=`${c}s`,0>=c?void window.location.reload():void setTimeout(b,1e3)}let c=60;b()}})()</script>'
        ));
    }
}
