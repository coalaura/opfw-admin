<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoggingHelper;
use App\Helpers\ServerAPI;
use App\Http\Controllers\Controller;
use App\Player;
use App\Server;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{

    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Renders the login view.
     */
    public function render()
    {
        if (sessionHelper()->get('isLogout')) {
            LoggingHelper::log('Rendering login view while coming from logout');

            sessionHelper()->forget('isLogout');
            sessionHelper()->forget('error');
        }

        if (license()) {
            // Huh, tf you doin here?
            return redirect('/');
        }

        return Inertia::render('Login');
    }

    /**
     * Single-Sign-On with a token from the FiveM server.
     *
     * @param string $token
     * @param string $licenseIdentifier
     */
    public function sso(Request $request, string $token, string $licenseIdentifier)
    {
        $session = sessionHelper();

        if (license()) {
            return redirect($session->get('lastVisit') ?? '/');
        }

        if (strlen($token) !== 18) {
            abort(401);
        }

        $server = Server::getFirstServer('name');

        if (!$server || !Str::startsWith($licenseIdentifier, "license:")) {
            abort(400);
        }

        $result = ServerAPI::validateAuthToken($server, $licenseIdentifier, $token);

        if (!$result || !is_array($result) || !$result['valid']) {
            abort(401);
        }

        $player = Player::query()
            ->where('license_identifier', '=', $licenseIdentifier)
            ->first();

        if (!$player || !$player->isStaff()) {
            abort(401);
        }

        $name = $player->getSafePlayerName();

        if (strlen($name) > 25) {
            $name = substr($name, 0, 22) . '...';
        }

        $session->put('user', $player->user_id);
        $session->put('name', $name);
        $session->put('discord', [
            'username' => $name,
            'discriminator' => $request->query('ref') ?? 'ext',
            'sso' => true
        ]);

        return redirect('/');
    }

}
