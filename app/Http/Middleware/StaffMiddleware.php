<?php

namespace App\Http\Middleware;

use App\Helpers\JwtHelper;
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
        // Check for staff status.
        $user = JwtHelper::user();

        if (!$user) {
            JwtHelper::logout();

            return redirectWith('/login', 'error', 'You are not logged in.');
        }

        if (!$user->isStaff()) {
            JwtHelper::logout();

            return redirectWith(
                '/login',
                'error',
                'Your staff status has changed, please log in again.'
            );
        }

        if ($request->isMethod('GET') && (!$request->ajax() || !empty($request->header('X-Inertia')))) {
            session_put('lastVisit', $request->path());
        }

        return $next($request);
    }
}
