<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\JwtHelper;
use App\Helpers\LoggingHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @package App\Http\Controllers\Auth
 */
class LogoutController extends Controller
{

    /**
     * Logs the user out.
     *
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        LoggingHelper::log('Logout triggered, dropping session');

        // Logout the user.
        JwtHelper::logout();

        session_put('isLogout', true);

        return redirect('/login');
    }

}
