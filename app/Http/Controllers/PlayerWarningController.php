<?php

namespace App\Http\Controllers;

use App\Helpers\DiscordAttachmentHelper;
use App\Helpers\GeneralHelper;
use App\Helpers\Mutex;
use App\Helpers\PermissionHelper;
use App\Helpers\RootHelper;
use App\Http\Requests\WarningStoreRequest;
use App\Player;
use App\Warning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlayerWarningController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Player $player
     * @param WarningStoreRequest $request
     * @return RedirectResponse
     */
    public function store(Player $player, WarningStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

		$msg = trim($data["message"]);

		if (Str::contains($msg, "This warning was generated automatically") || Str::startsWith($msg, "I removed this players ban.") || Str::startsWith($msg, "I scheduled the removal of this players ban for")) {
			return backWith('error', 'Something went wrong.');
		}

        $isSenior = $this->isSeniorStaff($request);

        if (!$isSenior && $data['warning_type'] === Warning::TypeHidden) {
            abort(401);
        }

        $warning = $player->warnings()->create(array_merge($data, [
            'issuer_id' => user()->user_id,
        ]));

        if ($warning) {
            DiscordAttachmentHelper::ensureMessageAttachments($warning);
        }

        return backWith('success', 'Warning/Note has been added successfully.');
    }

    /**
     * Updates the specified resource.
     *
     * @param Player $player
     * @param Warning $warning
     * @param WarningStoreRequest $request
     * @return RedirectResponse
     */
    public function update(Player $player, Warning $warning, WarningStoreRequest $request): RedirectResponse
    {
        if (!$warning->can_be_deleted) {
            abort(401);
        }

        $staffIdentifier = license();
        $issuer = $warning->issuer()->first();

        if (!$issuer || $staffIdentifier !== $issuer->license_identifier) {
            return backWith('error', 'You can only edit your own warnings/notes!');
        }

        $messageBefore = $warning->message;

        $warning->update($request->validated());

        DiscordAttachmentHelper::garbageCollectAttachments($messageBefore, $warning->message);
        DiscordAttachmentHelper::ensureMessageAttachments($warning);

        return backWith('success', 'Successfully updated warning/note');
    }

    /**
     * Refreshes the specified resource.
     *
     * @param Player $player
     * @param Warning $warning
     * @return RedirectResponse
     */
    public function refresh(Player $player, Warning $warning): RedirectResponse
    {
        if (!RootHelper::isCurrentUserRoot()) {
            abort(401);
        }

        DiscordAttachmentHelper::ensureMessageAttachments($warning);

        return backWith('success', 'Successfully refreshed warning/note');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param Player $player
     * @param Warning $warning
     * @return RedirectResponse
     */
    public function destroy(Request $request, Player $player, Warning $warning): RedirectResponse
    {
        $isSenior = $this->isSeniorStaff($request);

        if (!$warning->can_be_deleted && !$isSenior) {
            abort(401);
        }

        $warning->forceDelete();

        DiscordAttachmentHelper::unlinkMessageAttachments($warning);

        return backWith('success', 'The warning/note has successfully been deleted from the player\'s record.');
    }

    public function bulkDeleteWarnings(Request $request, Player $player)
    {
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_BULK_DELETE)) {
            abort(401);
        }

        $ids = $request->input('ids');

        if (!is_array($ids) || empty($ids) || sizeof($ids) > 10) {
            return backWith('error', 'Invalid request.');
        }

        $warnings = Warning::query()->where('player_id', $player->user_id)->whereIn('id', $ids)->get();

        foreach ($warnings as $warning) {
            $warning->forceDelete();

            DiscordAttachmentHelper::unlinkMessageAttachments($warning);
        }

        return backWith('success', 'The warnings have successfully been deleted from the player\'s record.');
    }

    /**
     * Toggle your reaction to the warning.
     *
     * @param Request $request
     * @param Player $player
     * @param Warning $warning
     */
    public function react(Request $request, Player $player, Warning $warning)
    {
        $emoji = $request->input('emoji');

        if (!$emoji || !in_array($emoji, Warning::Reactions)) {
            return $this->json(false, null, 'Invalid emoji');
        }

        $mutex = new Mutex('warning_reactions_' . $warning->id);

        $mutex->lockSync();

        $license = license();
        $reactions = $warning->reactions ?? [];

        $emojiReactions = $reactions[$emoji] ?? [];

        if (in_array($license, $emojiReactions)) {
            $emojiReactions = array_values(array_diff($emojiReactions, [$license]));
        } else {
            $emojiReactions[] = $license;
        }

        if (empty($emojiReactions)) {
            if (isset($reactions[$emoji])) {
                unset($reactions[$emoji]);
            }
        } else {
            $reactions[$emoji] = $emojiReactions;
        }

        $warning->update(['reactions' => $reactions]);

        $mutex->unlock();

        return $this->json(true, $warning->getReactions($license));
    }

    /**
     * Get the resolved reactions for the specified warning.
     *
     * @param Request $request
     * @param Player $player
     * @param Warning $warning
     */
    public function reactions(Request $request, Player $player, Warning $warning)
    {
        $license = license();

        return $this->json(true, $warning->getReactionsResolved($license));
    }
}
