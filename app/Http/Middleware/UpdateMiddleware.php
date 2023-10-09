<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to check if the panel is getting updated
 *
 * @package App\Http\Middleware
 */
class UpdateMiddleware
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
        $file = __DIR__ . '/../../../update';

        if (file_exists($file)) {
            $accepts = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : false;

            if ($accepts === 'application/json') {
                die(json_encode([
                    'status' => file_get_contents($file),
                    'time' => filemtime($file)
                ]));
            }

            http_response_code(418);

            die(file_get_contents(__DIR__ . '/../../../public/maintenance.html'));
        }

        return $next($request);
    }

}
