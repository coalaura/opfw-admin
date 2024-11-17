<?php

namespace App;

/**
 * @package App
 */
class PlayerStatus
{
    const STATUS_UNAVAILABLE = 'unavailable';
    const STATUS_OFFLINE     = 'offline';
    const STATUS_ONLINE      = 'online';

    /**
     * Connection Status
     *
     * @var string
     */
    public string $status = self::STATUS_UNAVAILABLE;

    /**
     * The ID in server (the one you see when pressing U)
     *
     * @var int
     */
    public int $serverId = 0;

    /**
     * The loaded character id if they are loaded into one
     *
     * @var int|null
     */
    public ?int $character = 0;

    /**
     * The subdomain of the server (c3s1)
     *
     * @var string
     */
    public string $serverName = '';

    /**
     * The metadata of the character
     *
     * @var array
     */
    public array $characterMetadata = [];

    public function __construct(string $status, string $serverName, int $serverId, ?int $character = null, ?array $characterMetadata = null)
    {
        $this->status = $status;
        $this->serverName = $serverName;
        $this->serverId = $serverId;
        $this->character = $character;

        $this->characterMetadata = $characterMetadata ?? [];
    }

    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    public function isInShell(): bool
    {
        return $this->isOnline() && $this->characterMetadata && in_array('in_shell', $this->characterMetadata);
    }
}
