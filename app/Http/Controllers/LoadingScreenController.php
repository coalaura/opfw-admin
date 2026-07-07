<?php
namespace App\Http\Controllers;

use App\AuditLog;
use App\Helpers\PermissionHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LoadingScreenController extends Controller
{
    /**
     * Renders the loading screen pictures.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_LOADING_SCREEN)) {
            abort(401);
        }

        $pictures = DB::table('loading_screen_images')->orderByDesc('id')->get();

        return Inertia::render('LoadingScreen/Index', [
            'pictures' => $pictures,
        ]);
    }

    /**
     * Delete a loading screen picture.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request, int $id): RedirectResponse
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_LOADING_SCREEN)) {
            abort(401);
        }

        DB::table('loading_screen_images')->where('id', $id)->delete();

        $user = user();

        AuditLog::log(license(), 'loading_screen.delete', 'loading_screen_image', $id, sprintf('%s deleted loading screen image #%d.', $user->consoleName(), $id), [
            'image_id' => $id,
        ]);

        return backWith('success', 'The picture has successfully been deleted.');
    }

    /**
     * Edit a loading screen picture.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function edit(Request $request, int $id): RedirectResponse
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_LOADING_SCREEN)) {
            abort(401);
        }

        $url = trim($request->input('image_url'));

        if (! $url || ! Str::startsWith($url, "https://")) {
            return backWith('error', 'Invalid url.');
        }

        $description = trim($request->input('description'));

        $included = $request->boolean('included', false);
        $excluded = $request->boolean('excluded', false);

        if ($included && $excluded) {
            $excluded = false;
        }

        DB::table('loading_screen_images')->where('id', $id)->update([
            'image_url'   => $url,
            'description' => $description,
            'included'    => $included,
            'excluded'    => $excluded,
        ]);

        $user = user();

        AuditLog::log(license(), 'loading_screen.update', 'loading_screen_image', $id, sprintf('%s edited loading screen image #%d.', $user->consoleName(), $id), [
            'image_id'   => $id,
            'image_url'  => $url,
            'description' => $description,
            'included'   => $included,
            'excluded'   => $excluded,
        ]);

        return backWith('success', 'The picture has successfully been edited.');
    }

    /**
     * Add a loading screen picture.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function add(Request $request): RedirectResponse
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_LOADING_SCREEN)) {
            abort(401);
        }

        $url = trim($request->input('image_url'));

        if (! $url || ! Str::startsWith($url, "https://")) {
            return backWith('error', 'Invalid url.');
        }

        $imageId = DB::table('loading_screen_images')->insertGetId([
            'image_url' => $url,
        ]);

        $user = user();

        AuditLog::log(license(), 'loading_screen.create', 'loading_screen_image', $imageId, sprintf('%s added loading screen image #%d.', $user->consoleName(), $imageId), [
            'image_id'  => $imageId,
            'image_url' => $url,
        ]);

        return backWith('success', 'The picture has successfully been added.');
    }
}
