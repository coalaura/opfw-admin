<?php

namespace App\Http\Controllers;

use App\Character;
use App\Helpers\PermissionHelper;
use App\Http\Resources\CharacterResource;
use App\Http\Resources\PlayerIndexResource;
use App\Http\Resources\TwitterPostResource;
use App\Http\Resources\TwitterUserResource;
use App\TwitterPost;
use App\TwitterUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TwitterController extends Controller
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

        $query = TwitterPost::query()->orderByDesc('time')->where('is_deleted', '=', '0');

        // Filtering by username.
        $this->searchQuery($request, $query, 'username', 'username');

        // Filtering by message.
        $this->searchQuery($request, $query, 'message', 'message');

        $query->leftJoin('twitter_accounts', 'twitter_accounts.id', '=', 'authorId');

        $page = Paginator::resolveCurrentPage('page');

        $query->select(['twitter_tweets.id', 'authorId', 'realUser', 'message', 'time', 'likes', 'username', 'is_verified', 'avatar_url']);
        $query->limit(15)->offset(($page - 1) * 15);

        $posts = TwitterPostResource::collection($query->get());

        $end = round(microtime(true) * 1000);

        return Inertia::render('Twitter/Index', [
            'posts'        => $posts,
            'filters'      => $request->all(
                'message',
                'username'
            ),
            'links'        => $this->getPageUrls($page),
            'time'         => $end - $start,
            'page'         => $page,
        ]);
    }

    /**
     * Shows a certain user and their tweets
     *
     * @param TwitterUser $user
     * @return Response
     */
    public function user(TwitterUser $user): Response
    {
        $page = Paginator::resolveCurrentPage('page');

        $tweets = TwitterPost::query()
            ->where('authorId', '=', $user->id)
            ->where('is_deleted', '=', '0')
            ->select(['id', 'authorId', 'realUser', 'message', 'time', 'likes'])
            ->orderByDesc('time')
            ->limit(15)->offset(($page - 1) * 15)
            ->get();

        $tweet = $tweets->first();

        if (!$tweet) {
            abort(404);
        }

        /**
         * @var $character Character|null
         */
        $character = Character::query()
            ->where('character_id', '=', $user->creator_cid ?? $tweet->realUser)
            ->get()->first();

        if (!$character) {
            abort(404);
        }

        return Inertia::render('Twitter/User', [
            'tweets'    => TwitterPostResource::collection($tweets),
            'character' => new CharacterResource($character),
            'player'    => new PlayerIndexResource($character->player()->get()->first()),
            'user'      => new TwitterUserResource($user),
            'links'     => $this->getPageUrls($page),
            'page'      => $page,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteTweets(Request $request): RedirectResponse
    {
		if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_TWITTER)) {
            abort(401);
        }

        $ids = $request->input('ids');

        if (empty($ids)) {
            return backWith('error', 'No tweets selected');
        }

        TwitterPost::query()->whereIn('id', $ids)->delete();

        return backWith('success', 'Successfully deleted tweets');
    }

    /**
     * Toggle a users verification
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function verify(Request $request, TwitterUser $user): RedirectResponse
    {
		if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_TWITTER_VERIFY)) {
            abort(401);
        }

        $user->update([
            'is_verified' => $user->is_verified ? 0 : 1,
        ]);

        return backWith('success', 'Successfully changed verification status');
    }

}
