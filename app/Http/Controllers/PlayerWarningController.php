<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarningStoreRequest;
use App\Player;
use App\Warning;
use App\Helpers\TranscriptHelper;
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
            TranscriptHelper::ensureMessageTranscripts($warning);
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

        TranscriptHelper::garbageCollectTranscripts($messageBefore, $warning->message);
        TranscriptHelper::ensureMessageTranscripts($warning);

        return backWith('success', 'Successfully updated warning/note');
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

        TranscriptHelper::unlinkMessageTranscripts($warning);

        $warning->forceDelete();

        return backWith('success', 'The warning/note has successfully been deleted from the player\'s record.');
    }

}
