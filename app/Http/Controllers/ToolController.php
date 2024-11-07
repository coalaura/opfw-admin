<?php

namespace App\Http\Controllers;

use App\Helpers\OPFWHelper;
use App\Server;
use Inertia\Inertia;

class ToolController extends Controller
{
    public function config()
    {
        $jobs = OPFWHelper::getDefaultJobsJSON(Server::getFirstServer() ?? '');

        return Inertia::render('Tools/Config', [
            'jobs' => $jobs['jobs'] ?? [],
        ]);
    }
}
