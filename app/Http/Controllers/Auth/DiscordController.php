<?php

namespace App\Http\Controllers\Auth;

use App\Player;
use App\Helpers\SessionHelper;
use Illuminate\Http\Request;
use App\Http\Middleware\StaffMiddleware;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;

/**
 * A controller to authenticate with discord.
 *
 * @package App\Http\Controllers\Auth
 */
class DiscordController extends Controller
{
    public function login(Request $request)
    {
        $clientId = env('DISCORD_OAUTH_ID');

        $url = 'https://discord.com/api/oauth2/authorize?client_id=' . $clientId . '&redirect_uri=' . urlencode($this->redirectUrl($request)) . '&response_type=code&scope=identify';

        return redirect($url);
    }

    public function complete(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirect('/login')->with('error', 'Missing oauth2 code.');
        }

        $token = $this->resolveAccessToken($request, $code);

        if (!$token) {
            return redirect('/login')->with('error', 'Failed to resolve access token.');
        }

        $user = $this->resolveUser($token);

        if (!$user) {
            return redirect('/login')->with('error', 'Failed to resolve user.');
        }

        // Process the user data.
        $session = SessionHelper::getInstance();

        $identifier = 'discord:' . $user['id'];

        $player = Player::query()
            ->where('last_used_identifiers', 'LIKE', '%' . $identifier . '%')
            ->first();

        if (!$player) {
            return redirect('/login')->with('error', 'No player with discord id "' . $user['id'] . '" found.');
        }

        $session->put('user', [
            'player' => $player->toArray()
        ]);

        // Session lock update
        $session->put('session_lock', StaffMiddleware::getSessionDetail());
        $session->put('session_detail', StaffMiddleware::getFingerprint());
        $session->put('last_updated', time());

        StaffMiddleware::updateSessionLock();

        $redirect = '/';

        if ($session->exists('returnTo')) {
            $redirect = $session->get('returnTo');
        }

        return redirect($redirect);
    }

    private function resolveAccessToken(Request $request, string $code)
    {
        try {
            $client = new Client();
            $res = $client->request('POST', 'https://discord.com/api/oauth2/token', [
                'form_params' => [
                    'client_id'     => env('DISCORD_OAUTH_ID'),
                    'client_secret' => env('DISCORD_OAUTH_SECRET'),
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'redirect_uri'  => $this->redirectUrl($request),
                    'scope'         => 'identify',
                ],
            ]);

            $response = $res->getBody()->getContents();

            $data = json_decode($response, true);

            if ($data && isset($data['access_token'])) {
                return $data['access_token'];
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private function resolveUser(string $accessToken)
    {
        try {
            $client = new Client();
            $res = $client->request('GET', 'https://discord.com/api/oauth2/@me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ]);

            $response = $res->getBody()->getContents();

            $data = json_decode($response, true);

            if ($data && isset($data['user'])) {
                return $data['user'];
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private function redirectUrl(Request $request)
    {
        return $request->getSchemeAndHttpHost() . '/auth/complete';
    }
}
