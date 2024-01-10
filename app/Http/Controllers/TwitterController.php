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

        if (intval($request->input('top', '0')) === 1) {
            // order by likes where time is within the last 15 days
            $query->where('time', '>=', time() - (60 * 60 * 24 * 15))->orderByDesc('likes');
        }

        $page = Paginator::resolveCurrentPage('page');

        $query->select(['twitter_tweets.id', 'authorId', 'realUser', 'message', 'time', 'likes', 'username', 'is_verified', 'avatar_url']);
        $query->limit(15)->offset(($page - 1) * 15);

        $posts = TwitterPostResource::collection($query->get());

        $end = round(microtime(true) * 1000);

        return Inertia::render('Twitter/Index', [
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

        $creatorCid = $user->creator_cid;

        if (!$creatorCid) {
            $tweet = $tweets->first();

            $creatorCid = $tweet ? $tweet->realUser : null;
        }

        /**
         * @var $character Character|null
         */
        $character = $creatorCid ? Character::query()
            ->where('character_id', '=', $creatorCid)
            ->get()->first() : null;

        return Inertia::render('Twitter/User', [
            'tweets'    => TwitterPostResource::collection($tweets),
            'character' => $character ? new CharacterResource($character) : null,
            'user'      => new TwitterUserResource($user),
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
    public function editTweet(Request $request, TwitterPost $post): RedirectResponse
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_TWITTER_EDIT)) {
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

        return backWith('success', 'Successfully edited tweet');
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
