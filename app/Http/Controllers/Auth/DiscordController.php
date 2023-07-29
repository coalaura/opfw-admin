<?php

namespace App\Http\Controllers\Auth;

use App\Player;
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

        $host = $request->getHttpHost();

        $url = 'https://discord.com/api/oauth2/authorize?client_id=' . $clientId . '&redirect_uri=' . urlencode($this->redirectUrl($request)) . '&response_type=code&scope=identify&state=' . $host;

        return redirect($url);
    }

    public function redirect(Request $request)
    {
        $state = $request->get('state');

        if (!$state) {
            return $this->text(400, 'Missing oauth2 state.');
        }

        $url = 'https://' . $state;

        $url .= '/auth/complete?code=' . $request->get('code');

        return redirect($url);
    }

    public function complete(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirectWith('/login', 'error', 'Missing oauth2 code.');
        }

        $token = $this->resolveAccessToken($request, $code);

        if (!$token) {
            return redirectWith('/login', 'error', 'Failed to resolve access token.');
        }

        $user = $this->resolveUser($token);

        if (!$user) {
            return redirectWith('/login', 'error', 'Failed to resolve user.');
        }

        // Process the user data.
        $session = sessionHelper();

        $id = $user['id'];
        $identifier = 'discord:' . $id;

        $player = Player::query()
            ->where('last_used_identifiers', 'LIKE', '%' . $identifier . '%')
            ->first();

        if ($player) {
            if (!$player->isStaff()) {
                return redirectWith('/login', 'error', "Player with last-used discord-id $id is not a staff member.");
            }
        } else {
            return redirectWith('/login', 'error', "No player with last-used discord-id $id not found.");
        }

        $session->put('user', $player->user_id);

        $session->put('discord', $user);

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

    private function redirectUrl(Request $request, bool $forceComplete = false)
    {
        $redirect = env('DISCORD_OAUTH_REDIRECT');

        if ($redirect && !$forceComplete) {
            return $redirect . '/auth/redirect';
        }

        return $request->getSchemeAndHttpHost() . '/auth/complete';
    }
}
