<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to check if user is a super admin on our game-servers.
 *
 * @package App\Http\Middleware
 */
class SuperAdminMiddleware
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
        // Check for super admin status.
        $user = user();

        if (!$user || !$user->isSuperAdmin()) {
            return backWith('error',
                'You must be a super-admin to do that! Contact a higher-up if you were shown this error by mistake.'
            );
        }

        return $next($request);
    }

}
