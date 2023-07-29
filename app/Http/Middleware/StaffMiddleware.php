<?php

namespace App\Http\Middleware;

use App\Helpers\LoggingHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Middleware to check if user is a staff member on our game-servers.
 *
 * @package App\Http\Middleware
 */
class StaffMiddleware
{
    /**
     * @var string
     */
    private $error = '';

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

        if (!$this->checkSessionLock()) {
            return redirectWith('/login', 'error', $this->error);
        }

        // Check for staff status.
        $player = $session->getPlayer();

        if (!$player) {
            return redirectWith('/login', 'error', 'You have to have connected to the server at least once before trying to log-in (Player not found).');
        }

        if (!$player->isStaff()) {
            return redirectWith(
                '/login',
                'error',
                'Your staff status has changed, please log in again.'
            );
        }

        $discord = $session->get('discord');

        if (!$discord) {
            return redirectWith(
                '/login',
                'error',
                'Missing discord in session.'
            );
        }

        return $next($request);
    }

    public static function getSessionDetail(): array
    {
        return [
            'db' => DB::connection()->getDatabaseName()
        ];
    }

    protected function checkSessionLock(): bool
    {
        $detail = self::getSessionDetail();

        $session = sessionHelper();
        if ($session->exists('session_lock')) {
            $lock = $session->get('session_lock');
            $print = self::getFingerprint();

            $valid = $lock === $print;

            if (!$valid) {
                $sDetail = $session->get('session_detail');

                LoggingHelper::log('StaffMiddleware session-lock is invalid');
                LoggingHelper::log($lock . ' != ' . $print);
                LoggingHelper::log('current.detail -> ' . json_encode($detail));
                LoggingHelper::log('session.detail -> ' . json_encode($sDetail));

                $this->error = 'Your session is invalid, please refresh this page or log in again.';
            }

            $session->put('session_detail', $detail);

            return $valid;
        } else {
            self::updateSessionLock();

            return true;
        }
    }

    public static function updateSessionLock()
    {
        $detail = [
            'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        ];

        $session = sessionHelper();

        $session->put('session_lock', self::getFingerprint());
        $session->put('session_detail', $detail);
    }

    public static function getFingerprint(): string
    {
        return md5(json_encode(self::getSessionDetail()));
    }
}
