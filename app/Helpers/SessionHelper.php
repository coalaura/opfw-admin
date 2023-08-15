<?php

namespace App\Helpers;

use App\Session;
use App\Player;

class SessionHelper
{
    const Cookie   = '_op_fw_session_store_i2';
    const Alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
    const Lifetime = 60 * 60 * 24 * 7;

    /**
     * Singleton instance
     *
     * @var SessionHelper|null
     */
    private static ?SessionHelper $instance = null;

    /**
     * The current sessions key
     *
     * @var string|null
     */
    private ?string $sessionKey = null;

    /**
     * The last set cookie
     *
     * @var string|null
     */
    private ?string $lastCookie = null;

    /**
     * The value of the current session
     *
     * @var array
     */
    private array $value = [];

    /**
     * Last retrieved session.
     */
    private ?Session $session = null;

    /**
     * Session player.
     */
    private ?Player $player = null;

    private function __construct()
    {
    }

    /**
     * Returns the current sessions key
     *
     * @return string
     */
    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * Checks if a given key is set in the session
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Gets a value from the session
     *
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        if (array_key_exists($key, $this->value)) {
            return $this->value[$key];
        }

        return null;
    }

    /**
     * Sets a value in the session
     *
     * @param string $key
     * @param mixed $value
     */
    public function put(string $key, $value)
    {
        $this->value[$key] = $value;

        $this->store();
    }

    /**
     * Forgets a value in the session
     *
     * @param string $key
     */
    public function forget(string $key)
    {
        if (array_key_exists($key, $this->value)) {
            unset($this->value[$key]);

            $this->store();
        }
    }

    /**
     * Clears all auth related data from the session
     */
    public function clearAuth()
    {
        if ($this->exists('user')) {
            $this->forget('user');
        }

        if ($this->exists('discord')) {
            $this->forget('discord');
        }
    }

    /**
     * Drops the current session
     */
    public static function drop()
    {
        $sessionKey = self::getSessionKeyFromCookie();

        if (!$sessionKey) return;

        LoggingHelper::log('Dropping session', $sessionKey);

        Session::query()->where('key', $sessionKey)->delete();

        self::$instance = null;
    }

    /**
     * Ensures the session in the database
     */
    private function ensure()
    {
        if ($this->session) return;

        // $this->cleanup();

        $this->session = Session::where('key', $this->sessionKey)->first();

        $metadata = Session::metadata();

        if ($this->session) {
            $this->session->update($metadata);
        } else {
            LoggingHelper::log('Session not found, creating again', $this->sessionKey);

            $metadata['key'] = $this->sessionKey;
            $metadata['data'] = json_encode($this->value);

            $this->session = Session::query()->create($metadata);
        }
    }

    /**
     * Loads the sessions data
     */
    private function load()
    {
        $session = $this->session;

        $data = json_decode($session->data, true);

        if (!$data) {
            LoggingHelper::log('Failed to decode session data', $this->sessionKey);
            $this->value = [];

            return;
        }

        $this->value = $data;
    }

    /**
     * Saves the session's data to the database
     */
    private function store()
    {
        $session = $this->session;

        $session->update([
            'data' => json_encode($this->value)
        ]);

        $this->updateCookie();
    }

    /**
     * Overrides the session cookie
     *
     * @param string $sessionKey
     */
    public function updateCookie()
    {
        if ($this->lastCookie === $this->sessionKey) return;

        $cookie = CLUSTER . self::Cookie;

        setcookie($cookie, $this->sessionKey, [
            'expires'  => time() + self::Lifetime,
            'secure'   => false,
            'httponly' => true,
            'path'     => '/',
            'samesite' => 'Lax',
        ]);

        $this->lastCookie = $this->sessionKey;
    }

    /**
     * Cleans up old sessions
     */
    private function cleanup()
    {
        $lifetime = time() - self::Lifetime;

        Session::query()
            ->where('last_accessed', '<', $lifetime)
            ->delete();
    }

    private static function getSessionKeyFromCookie(): ?string
    {
        $cookie = CLUSTER . self::Cookie;

        return !empty($_COOKIE[$cookie]) && is_string($_COOKIE[$cookie]) ? $_COOKIE[$cookie] : null;
    }

    /**
     * Returns an instance of the session helper
     *
     * @return SessionHelper
     */
    public static function getInstance(): SessionHelper
    {
        $cookie = CLUSTER . self::Cookie;

        if (self::$instance === null) {
            $helper = new SessionHelper();

            $helper->sessionKey = self::getSessionKeyFromCookie();

            if ($helper->sessionKey === null) {
                $helper->sessionKey = self::uniqueId();

                LoggingHelper::log('Session key is null, created new session key', $helper->sessionKey);
            }

            $helper->updateCookie();

            $helper->ensure();

            $helper->load();
            $helper->store();

            self::$instance = $helper;
        }

        return self::$instance;
    }

    /**
     * Generated a 20 character unique session key
     *
     * @return string
     */
    private static function uniqueId(): string
    {
        $characters = str_split(self::Alphabet);

        $str = '';
        for ($x = 0; $x < 30; $x++) {
            $str .= $characters[array_rand($characters)];
        }

        return $str;
    }

    public function getPlayer(): ?Player
    {
        $userId = $this->get('user');

        if (!$userId || !is_numeric($userId)) {
            return null;
        }

        if (!$this->player) {
            $this->player = Player::query()->where('user_id', '=', $userId)->first();
        }

        return $this->player;
    }

    public function getDiscord(): ?array
    {
        $discord = $this->get('discord');

        if (!$discord) {
            return null;
        }

        return $discord;
    }

    public function getCurrentLicense(): ?string
    {
        $player = $this->getPlayer();

        if (!$player) {
            return null;
        }

        return $player->license_identifier;
    }
}
