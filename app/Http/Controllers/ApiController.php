<?php

namespace App\Http\Controllers;

use App\Helpers\OPFWHelper;
use App\Helpers\PermissionHelper;
use App\Server;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function crafting(Request $request): Response
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_CRAFTING)) {
            abort(401);
        }

        $data = OPFWHelper::getCraftingTxt(Server::getFirstServer() ?? '');

        return (new Response($data, 200))
            ->header('Content-Type', 'text/plain');
    }
}
