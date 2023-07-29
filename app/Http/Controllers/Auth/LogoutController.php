<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoggingHelper;
use App\Helpers\SessionHelper;
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
        SessionHelper::drop();
        sessionHelper()->put('isLogout', true);

        // Redirect them to base path.
        return redirect('/');
    }

}
