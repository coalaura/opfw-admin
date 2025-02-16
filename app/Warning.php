<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A warning which can be given to players.
 *
 * @package App
 */
class Warning extends Model
{
    const ValidTypes = [
        self::TypeNote,
        self::TypeWarning,
        self::TypeStrike,
        self::TypeSystem,
        self::TypeHidden,
    ];

    const TypeNote    = 'note';
    const TypeWarning = 'warning';
    const TypeStrike  = 'strike';
    const TypeSystem  = 'system';
    const TypeHidden  = 'hidden';

    const Reactions = [
        'blinks',
        'bruh',
        'chair_stare',
        'confused_pika',
        'cool_cat',
        'crying',
        'fufu',
        'grrr',
        'happy_cat',
        'hmmm',
        'kekw',
        'pepoo_clap',
        'pog',
        'shocked',
        'shrug',
        'touch_grass',
        'uhhhh',
        'zerochamp',
    ];

    const AnimatedReactions = [
        'bed_time',
        'blinks',
        'bruh',
        'cat_chew',
        'cat_jam',
        'jigglin',
        'monkey_jam',
        'nodders',
        'pepoo_clap',
        'pet_kitty',
        'touch_grass',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'player_id',
        'issuer_id',
        'message',
        'warning_type',
        'can_be_deleted',
        'reactions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'reactions' => 'array',
    ];

    /**
     * Gets the player relationship.
     *
     * @return BelongsTo
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id', 'user_id');
    }

    /**
     * Gets the issuer relationship.
     *
     * @return BelongsTo
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'issuer_id', 'user_id');
    }

    public static function getAllReactions(): array
    {
        $reactions = [];

        foreach (self::Reactions as $emoji) {
            $reactions[$emoji] = "/images/reactions/{$emoji}.png";
        }

        foreach (self::AnimatedReactions as $emoji) {
            $reactions[$emoji] = "/images/reactions/{$emoji}.gif";
        }

        return $reactions;
    }

    public function getReactions(?string $forLicense = null): array
    {
        $raw = $this->reactions ?? [];

        $reactions = [
            'mine' => [],
            'all'  => [],
        ];

        foreach (self::Reactions as $emoji) {
            $reacted = $raw[$emoji] ?? [];

            if (empty($reacted)) {
                continue;
            }

            if ($forLicense && in_array($forLicense, $reacted)) {
                $reactions['mine'][] = $emoji;
            }

            $reactions['all'][$emoji] = sizeof($reacted);
        }

        return $reactions;
    }

    public function getReactionsResolved(?string $forLicense = null): array
    {
        $raw = $this->reactions ?? [];

        $licenses = [];

        foreach (self::Reactions as $emoji) {
            $reacted = $raw[$emoji] ?? [];

            if (empty($reacted)) {
                continue;
            }

            $licenses = array_merge($licenses, $reacted);
        }

        $players = Player::fetchLicensePlayerNameMap($licenses, null);

        $reactions = [];

        foreach (self::Reactions as $emoji) {
            $reacted = $raw[$emoji] ?? [];

            if (empty($reacted)) {
                continue;
            }

            $reactions[$emoji] = array_map(function ($license) use ($players) {
                return $players[$license] ?? $license;
            }, $reacted);
        }

        return $reactions;
    }

}
