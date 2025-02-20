<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoggingHelper;
use App\Http\Controllers\Controller;
use App\Player;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

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

        $url = 'https://discord.com/api/oauth2/authorize?client_id=' . $clientId . '&redirect_uri=' . urlencode($this->redirectUrl($request)) . '&response_type=code&scope=identify&prompt=none&state=' . $host;

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

        $tokens = $this->resolveTokens($request, $code);

        if (!$tokens) {
            return redirectWith('/login', 'error', 'Failed to resolve access token.');
        }

        $accessToken  = $tokens['access'];
        $refreshToken = $tokens['refresh'];

        $user = $this->resolveUser($accessToken);

        if (!$user) {
            return redirectWith('/login', 'error', 'Failed to resolve user.');
        }

        // Process the user data.
        $session = sessionHelper();

        $id = $user['id'];

        $player = Player::query()
            ->where('last_used_identifiers', 'LIKE', '%discord:' . $id . '%')
            ->orderBy('last_connection', 'DESC')
            ->first();

        if ($player) {
            if (!$player->isStaff()) {
                LoggingHelper::log(sprintf('Player %s found for discord id %s, but is not staff', $player->license_identifier, $id));

                return redirectWith('/login', 'error', "Player with last-used discord-id $id: \"" . $player->getSafePlayerName() . "\" is not a staff member.");
            }
        } else {
            LoggingHelper::log(sprintf('No player found for discord id %s', $id));

            return redirectWith('/login', 'error', "No player with last-used discord-id $id not found. Connect to the FiveM server with your discord linked first.");
        }

        $session->put('user', $player->user_id);
        $session->put('name', $player->getSafePlayerName());
        $session->put('discord', $user);

        $session->put('tokens', [
            'expires' => $tokens['expires'],
            'access'  => $accessToken,
            'refresh' => $refreshToken,
        ]);

        return redirect($session->get('lastVisit') ?? '/');
    }

    public function refresh(Request $request)
    {
        $session = sessionHelper();

        $tokens = $session->get('tokens');

        if (!$tokens) {
            LoggingHelper::log('No tokens found in session, redirecting to login page');

            return $this->login($request);
        }

        $accessToken = $tokens['access'] ?? '';
        $expires = $tokens['expires'] ?? 0;

        if (!$accessToken || $expires <= time()) {
            $refreshToken = $tokens['refresh'];

            if (!$refreshToken) {
                LoggingHelper::log('Access token expired and no refresh token found, redirecting to login page');

                return $this->login($request);
            }

            $tokens = $this->resolveTokens($request, null, $refreshToken);

            if (!$tokens) {
                LoggingHelper::log('Failed to refresh access token with refresh token, redirecting to login page');

                return $this->login($request);
            }

            LoggingHelper::log('Refreshed access token with refresh token');

            $accessToken  = $tokens['access'];

            $session->put('tokens', $tokens);
        }

        $user = $this->resolveUser($accessToken);

        if (!$user) {
            LoggingHelper::log('Failed to resolve discord user, redirecting to login page');

            return $this->login($request);
        }

        $session->put('discord', $user);

        LoggingHelper::log('Refreshed discord user with access token');

        return back();
    }

    private function resolveTokens(Request $request, ?string $code, ?string $refreshToken = null)
    {
        $data = $data = [
            'client_id'     => env('DISCORD_OAUTH_ID'),
            'client_secret' => env('DISCORD_OAUTH_SECRET'),
        ];

        if ($code) {
            $data = array_merge($data, [
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'redirect_uri' => $this->redirectUrl($request),
                'scope'        => 'identify',
            ]);
        } else if ($refreshToken) {
            $data = array_merge($data, [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);
        }

        try {
            $client = new Client();
            $res    = $client->request('POST', 'https://discord.com/api/oauth2/token', [
                'form_params' => $data,
            ]);

            $response = $res->getBody()->getContents();

            $data = json_decode($response, true);

            if ($data && isset($data['access_token'])) {
                return [
                    'access'  => $data['access_token'],
                    'refresh' => $data['refresh_token'],
                    'expires' => time() + $data['expires_in'],
                ];
            }
        } catch (\Throwable $e) {
            LoggingHelper::log(sprintf('Failed to resolve discord access token: %s', $e->getMessage()));
        }

        return null;
    }

    private function resolveUser(string $accessToken)
    {
        try {
            $client = new Client();
            $res    = $client->request('GET', 'https://discord.com/api/oauth2/@me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $response = $res->getBody()->getContents();

            $data = json_decode($response, true);

            if ($data && isset($data['user'])) {
                return $data['user'];
            }
        } catch (\Throwable $e) {
            LoggingHelper::log(sprintf('Failed to resolve discord user: %s', $e->getMessage()));
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
