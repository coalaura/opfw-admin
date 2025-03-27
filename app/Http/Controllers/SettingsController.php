<?php

namespace App\Http\Controllers;

use App\Session;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Helpers\ChunkedHelper;

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
            'active' => session_token(),
        ]);
    }

    public function updateSetting(Request $request, string $key)
    {
        $chunked = new ChunkedHelper();

        $user = user();

        $value = $request->input('value');

        $success = true;
        $error = '';

        try {
            $user->setPanelSetting($key, $value, function ($progress) use ($chunked) {
                $chunked->sendChunk([
                    'status' => $progress
                ]);
            });
        } catch (\Exception $e) {
            $success = false;
            $error = $e->getMessage();
        }

        $chunked->end();

        return $this->json($success, $user->getPanelSetting($key), $error);
    }

}
