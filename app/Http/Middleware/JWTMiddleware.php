<?php

namespace App\Http\Middleware {

    use Closure;
    use Illuminate\Http\Request;
    use App\Helpers\JwtHelper;

    class JWTMiddleware
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
            JwtHelper::init();

            try {
                $response = $next($request);
            } catch (\Throwable $t) {
                throw $t;
            } finally {
                JwtHelper::shutdown();
            }

            return $response;
        }
    }
}

// Shortcuts
namespace {
    use App\Player;
    use App\Helpers\JwtHelper;

    function session_get(string $key)
    {
        return JwtHelper::get($key);
    }

    function session_put(string $key, $value)
    {
        return JwtHelper::put($key, $value);
    }

    function session_forget(string $key)
    {
        return JwtHelper::forget($key);
    }

    function session_token(): ?string
    {
        return JwtHelper::token();
    }

    function user(): ?Player
    {
        return JwtHelper::user();
    }

    function consoleName(): ?string
    {
        $user = user();

        if (!$user) return null;

        return $user->getSafePlayerName() . ' (' . $user->license_identifier . ')';
    }

    function license(): ?string
    {
        $user = user();

        if (!$user) return null;

        return $user->license_identifier;
    }

    function discord(): ?array
    {
        return session_get('discord');
    }
}
