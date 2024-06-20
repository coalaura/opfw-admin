<?php

namespace App\Http\Controllers;

use App\Container;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContainerController extends Controller
{
    /**
     * List all containers.
     *
     * @return Response
     */
    public function containers(Request $request): Response
    {
        $containers = Container::all() ?? [];
        $items      = Container::items() ?? [];

        return Inertia::render('Containers', [
            'containers' => $containers,
            'items'      => $items,
        ]);
    }
}
