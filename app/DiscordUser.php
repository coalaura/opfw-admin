<?php

namespace App;

use App\Helpers\CacheHelper;
use App\Helpers\LoggingHelper;

class DiscordUser
{
    const CacheKey = 'discord_users_v2';

    public string  $id            = '';
    public string  $username      = '';
    public string  $discriminator = '';
    private string $avatar        = '';

    /**
     * Returns the avatar link
     *
     * @return string
     */
    public function getAvatar(): string
    {
        return $this->avatar ? 'https://cdn.discordapp.com/avatars/' . $this->id . '/' . $this->avatar . '.png' : '';
    }

    /**
     * Loads a user
     *
     * @param string $discordId
     * @return DiscordUser|null
     */
    public static function getUser(string $discordId): ?DiscordUser
    {
        $key = self::CacheKey . '_' . md5($discordId);

        if (CacheHelper::exists($key)) {
            $cache = CacheHelper::read($key) ?? null;

            if (self::fromArray($cache)) {
                return self::fromArray($cache);
            }
        }

        $token = env('DISCORD_BOT_TOKEN', '');

        if (!$token) {
            return null;
        }

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://discord.com/api/v10/users/' . $discordId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bot ' . $token
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $data = json_decode($response, true);

            if ($data && is_array($data)) {
                $user = self::fromArray($data);

                if ($user) {
                    CacheHelper::write($key, $user->toArray(true), CacheHelper::DAY);
                }

                return $user;
            }
        } catch (\Throwable $e) {
            LoggingHelper::log(sprintf('Failed to load discord user: %s', $e->getMessage()));
        }

        return null;
    }

    /**
     * Creates a user from an array
     *
     * @param array $data
     * @return DiscordUser|null
     */
    public static function fromArray(array $data): ?DiscordUser
    {
        $required = [
            'id',
            'username',
            'avatar',
        ];

        foreach ($required as $key) {
            if (!isset($data[$key]) || empty($data[$key])) {
                return null;
            }
        }

        if ($data['discriminator'] === "0") {
            $data['discriminator'] = false;
        }

        $user = new DiscordUser();
        $user->id = $data['id'];
        $user->username = $data['username'];
        $user->discriminator = $data['discriminator'] ?? false;
        $user->avatar = $data['avatar'];

        return $user;
    }

    /**
     * Returns the user as an array
     *
     * @param bool $storeArray
     * @return array
     */
    public function toArray(bool $storeArray = false): array
    {
        return [
            'id'            => $this->id,
            'username'      => $this->username,
            'discriminator' => $this->discriminator,
            'avatar'        => $storeArray ? $this->avatar : $this->getAvatar(),
        ];
    }
}
