<?php
namespace App\Http\Middleware;

use App\Helpers\JwtHelper;
use App\Helpers\LoggingHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Middleware to check if user is a staff member on our game-servers.
 *
 * @package App\Http\Middleware
 */
class StaffMiddleware
{
    const SkipPaths = [
        '/login',
        '/logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->rememberPath($request);

        // Check for staff status.
        $user = JwtHelper::user();

        if (! $user) {
            JwtHelper::logout();

            return redirectWith('/login', 'error', 'You are not logged in.');
        }

        if (! $user->isStaff()) {
            JwtHelper::logout();

            return redirectWith(
                '/login',
                'error',
                'Your staff status has changed, please log in again.'
            );
        }

        return $next($request);
    }

    private function rememberPath(Request $request)
    {
        if (!$request->isMethod('GET')) {
            return;
        }

        if ($request->ajax() && empty($request->header('X-Inertia'))) {
            return;
        }

        $path = $request->path();

        if (!Str::startsWith($path, '/')) {
            $path = '/' . $path;
        }

        foreach(self::SkipPaths as $skip) {
            if (Str::startsWith($path, $skip)) {
                return;
            }
        }

        session_put('lastVisit', $path);

        LoggingHelper::log(sprintf('Remembering lastVisit: %s', $path));
    }
}
