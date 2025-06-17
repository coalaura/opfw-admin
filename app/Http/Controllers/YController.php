<?php

namespace App\Http\Controllers;

use App\Character;
use App\Helpers\PermissionHelper;
use App\Http\Resources\CharacterResource;
use App\Http\Resources\YPostResource;
use App\Http\Resources\YUserResource;
use App\YPost;
use App\YUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Inertia\Inertia;
use Inertia\Response;

class YController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $start = round(microtime(true) * 1000);

        $query = YPost::query()->where('is_deleted', '=', '0');

        // Filtering by username.
        $this->searchQuery($request, $query, 'username', 'username');

        // Filtering by message.
        $this->searchQuery($request, $query, 'message', 'message');

        $query->leftJoin('y_accounts', 'y_accounts.id', '=', 'authorId');

        if (intval($request->input('top', '0')) === 1) {
            // y_tweets.TIME > CURRENT_TIMESTAMP() - INTERVAL '15' DAY
            $query->where('time', '>', date('Y-m-d H:i:s', strtotime('-15 days')));

            $query->orderByDesc('likes');
        } else {
            $query->orderByDesc('time');
        }

        $page = Paginator::resolveCurrentPage('page');

        $query->select(['y_tweets.id', 'authorId', 'realUser', 'message', 'time', 'likes', 'username', 'is_verified', 'avatar_url']);
        $query->limit(30)->offset(($page - 1) * 30);

        $posts = YPostResource::collection($query->get());

        $end = round(microtime(true) * 1000);

        return Inertia::render('Y/Index', [
            'posts'   => $posts,
            'filters' => $request->all(
                'message',
                'username',
                'top'
            ),
            'links'   => $this->getPageUrls($page),
            'time'    => $end - $start,
            'page'    => $page,
        ]);
    }

    /**
     * Shows a certain user and their yells
     *
     * @param YUser $user
     * @return Response
     */
    public function user(YUser $user): Response
    {
        $page = Paginator::resolveCurrentPage('page');

        $yells = YPost::query()
            ->where('authorId', '=', $user->id)
            ->where('is_deleted', '=', '0')
            ->select(['id', 'authorId', 'realUser', 'message', 'time', 'likes'])
            ->orderByDesc('time')
            ->limit(30)->offset(($page - 1) * 30)
            ->get();

        $creatorCid = $user->creator_cid;

        if (!$creatorCid) {
            $yell = $yells->first();

            $creatorCid = $yell ? $yell->realUser : null;
        }

        /**
         * @var $character Character|null
         */
        $character = $creatorCid ? Character::query()
            ->where('character_id', '=', $creatorCid)
            ->get()->first() : null;

        return Inertia::render('Y/User', [
            'yells'    => YPostResource::collection($yells),
            'character' => $character ? new CharacterResource($character) : null,
            'user'      => new YUserResource($user),
            'links'     => $this->getPageUrls($page),
            'page'      => $page,
        ]);
    }

    /**
     * Edit the specified resource from storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function editYell(Request $request, YPost $post): RedirectResponse
    {
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_Y_EDIT)) {
            abort(401);
        }

        $update = [];

        if ($request->has('message')) {
            $update['message'] = $request->input('message');
        }

        if ($request->has('likes')) {
            $likes = intval($request->input('likes'));

            if (is_int($likes) && $likes >= 0) {
                $update['likes'] = $likes;
            }
        }

        $post->update($update);

        return backWith('success', 'Successfully edited yell');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteYells(Request $request): RedirectResponse
    {
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_Y)) {
            abort(401);
        }

        $ids = $request->input('ids');

        if (empty($ids)) {
            return backWith('error', 'No yells selected');
        }

        YPost::query()->whereIn('id', $ids)->delete();

        return backWith('success', 'Successfully deleted yells');
    }

    /**
     * Toggle a users verification
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function verify(Request $request, YUser $user): RedirectResponse
    {
        if (!PermissionHelper::hasPermission(PermissionHelper::PERM_Y_VERIFY)) {
            abort(401);
        }

        $user->update([
            'is_verified' => $user->is_verified ? 0 : 1,
        ]);

        return backWith('success', 'Successfully changed verification status');
    }

}
