<?php

namespace App\Http\Middleware {
    use Closure;
    use Illuminate\Http\Request;
    use Illuminate\Support\Str;

    class StartSession
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
            $path = $request->path();

            if (Str::startsWith($path, 'auth/')) {
                return $next($request);
            }

            // Force initialization of the session
            sessionHelper();

            return $next($request);
        }
    }
}

// Shortcuts for sessionHelper
namespace {
    use App\Player;
    use App\Helpers\SessionHelper;

    function sessionHelper(): ?SessionHelper
    {
        return SessionHelper::getInstance();
    }

    function sessionKey(): ?string
    {
        return sessionHelper()->getSessionKey();
    }

    function user(): ?Player
    {
        return sessionHelper()->getPlayer();
    }

    function license(): ?string
    {
        return sessionHelper()->getCurrentLicense();
    }
}
