<?php

namespace App\Http\Middleware;

use App\Helpers\LoggingHelper;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to log requests
 *
 * @package App\Http\Middleware
 */
class LogMiddleware
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
        $player = user();
        $name = "N/A";

		if ($player) {
			$name = $player->getSafePlayerName();
		}

        LoggingHelper::log('ACCEPTED ' . $name);

        return $next($request);
    }

}
