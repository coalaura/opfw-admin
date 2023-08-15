<?php

namespace App\Http\Controllers;

use App\Session;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Settings', [
            'active' => sessionKey(),
            'sessions' => Session::getActive()
        ]);
    }

}
