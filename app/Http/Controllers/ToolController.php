<?php

namespace App\Http\Controllers;

use App\Helpers\ServerAPI;
use Inertia\Inertia;

class ToolController extends Controller
{
    public function config()
    {
        $jobs  = ServerAPI::getDefaultJobs();
        $items = ServerAPI::getItems();

        return Inertia::render('Tools/Config', [
            'jobs'  => $jobs['jobs'] ?? [],
            'items' => $items ?? [],
        ]);
    }
}
