<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoggingHelper;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

/**
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{

    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Renders the login view.
     */
    public function render()
    {
        if (sessionHelper()->get('isLogout')) {
            LoggingHelper::log('Rendering login view while coming from logout');

            sessionHelper()->forget('isLogout');
            sessionHelper()->forget('error');
        }

        if (license()) {
            // Huh, tf you doin here?
            return redirect('/');
        }

        return Inertia::render('Login');
    }

}
