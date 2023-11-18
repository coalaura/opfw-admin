<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @package App
 */
class TwitterUser extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'twitter_accounts';

    /**
     * Whether to use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'username',
        'password',
        'avatar_url',
        'creator_cid',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function getAvatar() {
        if (empty($this->avatar_url) || Str::contains($this->avatar_url, 'cfx-nui-gcphone')) {
            return '/images/default_profile.png';
        }

        return $this->avatar_url;
    }

    /**
     * Returns a map of twitter_userid->twitter_user
     * This is used instead of a left join as it appears to be a lot faster
     *
     * @param array $source
     * @param string $sourceKey
     * @return array
     */
    public static function fetchIdMap(array $source, string $sourceKey): array
    {
        $ids = [];
        foreach ($source as $entry) {
            if (!in_array($entry[$sourceKey], $ids)) {
                $ids[] = $entry[$sourceKey];
            }
        }

        $users = self::query()->whereIn('id', $ids)->select([
            'id', 'username', 'avatar_url', 'is_verified',
        ])->get();
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user->id] = [
                'username'    => $user->username,
                'avatar_url'  => $user->getAvatar(),
                'is_verified' => $user->is_verified,
            ];
        }

        if (empty($userMap)) {
            $userMap['empty'] = 'empty';
        }

        return $userMap;
    }

}
