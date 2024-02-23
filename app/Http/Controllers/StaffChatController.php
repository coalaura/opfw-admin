<?php

namespace App\Http\Controllers;

use App\Helpers\OPFWHelper;
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
        $emotes = OPFWHelper::getChatEmotesJSON(Server::getFirstServer());

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

        $serverIp = Server::getFirstServer();

        $status = OPFWHelper::staffChat($serverIp, license(), $message);

        if (!$status->status) {
            return $this->json(false, null, $status->message);
        }

        return $this->json(true, $message);
    }

    public function staffChat()
    {
        $logs = DB::select("SELECT player_name, action, details, UNIX_TIMESTAMP(timestamp) as timestamp FROM user_logs LEFT JOIN users ON identifier = license_identifier WHERE (action = 'Staff Message' or action = 'Report') ORDER BY timestamp DESC");

        $text = [];

        $lastDay = false;

        foreach ($logs as $log) {
            $date = date('D, jS M Y', $log->timestamp);

            if ($date != $lastDay) {
                if ($lastDay) {
                    $text[] = '</table>';
                }

                $text[] = "<b style='border-bottom: 1px dashed #fff;margin: 10px 0 5px;display: inline-block;'>- - - " . $date . " - - -</b><table>";

                $lastDay = $date;
            }

            $time = date('H:i', $log->timestamp);

            if ($log->action == 'Report') {
                $class = 'report';

                $re = '/(?<=following message: `).+?(?=`$)/m';
            } else {
                $class = 'staff';

                $re = '/(?<=staff chat: `).+?(?=`$)/m';
            }

            $message = preg_match_all($re, $log->details, $matches, PREG_SET_ORDER, 0);

            if (isset($matches[0][0])) {
                $message = $matches[0][0];
            } else {
                $message = $log->details;
            }

            $text[] = '<tr class="' . $class . '"><td>' . $time . '</td><td><b>' . $log->player_name . '</b></td><td><i>' . $message . '</i></td></tr>';
        }

        return $this->fakeText(200, implode("\n", $text) . '</table><style>td:not(:last-child){white-space:nowrap}td{padding:5px 7px;font-size:13px}tr.staff{background:rgba(215,105,255,.2)}tr.staff:nth-child(odd){background:rgba(215,105,255,.15)}table{border-collapse:collapse}tr.report{background:rgba(105,255,121,.2)}tr.report:nth-child(odd){background:rgba(105,255,121,.15)}</style>');
    }

}
