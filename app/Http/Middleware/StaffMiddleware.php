<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to check if user is a staff member on our game-servers.
 *
 * @package App\Http\Middleware
 */
class StaffMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $session = sessionHelper();

        // Check for staff status.
        $player = $session->getPlayer();

        if (!$player) {
            $session->clearAuth();

            return redirectWith('/login', 'error', 'You have to have connected to the server at least once before trying to log-in (Player not found).');
        }

        if (!$player->isStaff()) {
            $session->clearAuth();

            return redirectWith(
                '/login',
                'error',
                'Your staff status has changed, please log in again.'
            );
        }

        $discord = $session->get('discord');

        if (!$discord) {
            $session->clearAuth();

            return redirectWith(
                '/login',
                'error',
                'Missing discord in session.'
            );
        }

        $name = $player->getSafePlayerName();

        if ($session->get('name') !== $name) {
            $session->put('name', $name);
        }

        return $next($request);
    }
}
