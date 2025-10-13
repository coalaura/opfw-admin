<?php
namespace App\Http\Controllers\Auth;

use App\Helpers\JwtHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\ServerAPI;
use App\Http\Controllers\Controller;
use App\Player;
use App\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{

    /**
     * Renders the login view.
     */
    public function render()
    {
        if (session_get('isLogout')) {
            LoggingHelper::log('Rendering login view while coming from logout');

            session_forget('isLogout');
            session_forget('flash_error');
        }

        if (license()) {
            // Huh, tf you doin here?
            return redirect(session_get('lastVisit') ?? '/');
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
        if (license()) {
            return redirect(session_get('lastVisit') ?? '/');
        }

        if (strlen($token) !== 18) {
            abort(401);
        }

        $server = Server::getFirstServer('name');

        if (! $server || ! Str::startsWith($licenseIdentifier, "license:")) {
            abort(400);
        }

        $result = ServerAPI::validateAuthToken($server, $licenseIdentifier, $token);

        if (! $result || ! is_array($result) || ! $result['valid']) {
            abort(401);
        }

        $player = Player::query()
            ->where('license_identifier', '=', $licenseIdentifier)
            ->first();

        if (! $player || ! $player->isStaff()) {
            abort(401);
        }

        $name = $player->getSafePlayerName();

        if (strlen($name) > 25) {
            $name = substr($name, 0, 22) . '...';
        }

        LoggingHelper:log(sprintf("SSO login success for user %s (#%d)", $name, $player->user_id));

        JwtHelper::login($player, [
            'id'            => $player->user_id,
            'username'      => $name,
            'discriminator' => $request->query('ref') ?? 'ext',
            'sso'           => true,
        ]);

        return redirect('/');
    }

}
