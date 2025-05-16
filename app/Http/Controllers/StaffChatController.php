<?php

namespace App\Http\Controllers;

use App\Helpers\OPFWHelper;
use App\Helpers\ServerAPI;
use App\Player;
use App\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StaffChatController extends Controller
{

    /**
     * Renders the staff chat.
     *
     * @param Request $request
     * @return Response
     */
    public function chat(Request $request): Response
    {
        $emotes = ServerAPI::getChatEmotes();

        return Inertia::render('StaffChat', [
            'emotes' => $emotes,
        ]);
    }

    /**
     * Add external staff messages
     *
     * @param Request $request
     */
    public function sendChat(Request $request)
    {
        $message = trim($request->input('message'));

        if (!$message || strlen($message) > 250) {
            return $this->json(false, null, 'Invalid message length');
        }

        $serverUrl = Server::getFirstServer("url");

        $status = OPFWHelper::staffChat($serverUrl, license(), $message);

        if (!$status->status) {
            return $this->json(false, null, $status->message);
        }

        user()->trackStatistics('sent-staff-chat');

        return $this->json(true, $message);
    }

}
