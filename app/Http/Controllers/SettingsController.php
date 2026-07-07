<?php

namespace App\Http\Controllers;

use App\AuditLog;
use App\Helpers\ChunkedHelper;
use App\Session;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

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

        $before = $user->getPanelSetting($key);

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

        if ($success) {
            AuditLog::log(license(), 'settings.update', 'player', $user->license_identifier, sprintf('%s updated their panel setting `%s`.', $user->consoleName(), $key), [
                'player' => $user->license_identifier,
                'key'    => $key,
                'before' => $before,
                'after'  => $value,
            ]);
        }

        return $this->json($success, $user->getPanelSetting($key), $error);
    }

}
