<?php
namespace App\Helpers;

use App\Helpers\LoggingHelper;
use App\OPFWResponse;
use App\Player;
use App\Server;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Throwable;

class OPFWHelper
{
    private static $isSocketDown = false;

    /**
     * Sends a staff pm to a player
     *
     * @param string $staffLicenseIdentifier
     * @param Player $player
     * @param string $message
     * @return OPFWResponse
     */
    public static function staffPM(string $staffLicenseIdentifier, Player $player, string $message): OPFWResponse
    {
        if (! $message) {
            return new OPFWResponse(false, 'Your message cannot be empty');
        }

        $status = Player::getOnlineStatus($player->license_identifier, false);
        if (! $status->isOnline()) {
            return new OPFWResponse(false, 'Player is offline.');
        }

        $response = self::executeRoute(Server::getServerURL($status->serverName) . 'execute/privateMessage', [
            'licenseIdentifier' => $staffLicenseIdentifier,
            'targetSource'      => $status->serverId,
            'message'           => $message,
        ]);

        if ($response->status) {
            $response->message = 'Staff Message has been sent successfully.';
        }

        return $response;
    }

    /**
     * Sends a staff chat message
     *
     * @param string $serverIp
     * @param string $staffLicenseIdentifier
     * @param string $message
     * @return OPFWResponse
     */
    public static function staffChat(string $serverIp, string $staffLicenseIdentifier, string $message): OPFWResponse
    {
        if (! $message) {
            return new OPFWResponse(false, 'Your message cannot be empty');
        }

        $response = self::executeRoute($serverIp . 'execute/staffMessage', [
            'licenseIdentifier' => $staffLicenseIdentifier,
            'message'           => $message,
        ]);

        if ($response->status) {
            $response->message = 'Staff Chat Message has been sent successfully.';
        }

        return $response;
    }

    /**
     * Sends a server message
     *
     * @param string $message
     * @return OPFWResponse
     */
    public static function serverAnnouncement(string $serverUrl, string $message): OPFWResponse
    {
        if (! $message) {
            return new OPFWResponse(false, 'Your message cannot be empty.');
        }

        $response = self::executeRoute($serverUrl . 'execute/announcementMessage', [
            'announcementMessage' => $message,
        ]);

        if ($response->status) {
            $response->message = 'Server Announcement has been posted successfully.';
        } else {
            $response->message = 'Failed to post server announcement.';
        }

        return $response;
    }

    /**
     * Kicks a player from the server
     *
     * @param string $staffPlayerName
     * @param Player $player
     * @param string $reason
     * @return OPFWResponse
     */
    public static function kickPlayer(string $staffPlayerName, Player $player, string $reason): OPFWResponse
    {
        $license = $player->license_identifier;

        $status = Player::getOnlineStatus($license, false);
        if (! $status->isOnline()) {
            return new OPFWResponse(false, 'Player is offline.');
        }

        if (env('HIDE_BAN_CREATOR')) {
            $staffPlayerName = "a staff member";
        }

        $response = self::executeRoute(Server::getServerURL($status->serverName) . 'execute/kickPlayer', [
            'licenseIdentifier'       => $license,
            'reason'                  => 'You have been kicked by ' . $staffPlayerName . ' for reason `' . $reason . '`',
            'removeReconnectPriority' => false,
        ]);

        if ($response->status) {
            $response->message = 'Kicked player from the server.';
        }

        return $response;
    }

    /**
     * Revives a player in the server
     *
     * @param string $licenseIdentifier
     * @return OPFWResponse
     */
    public static function revivePlayer(string $licenseIdentifier): OPFWResponse
    {
        $status = Player::getOnlineStatus($licenseIdentifier, false);
        if (! $status->isOnline()) {
            return new OPFWResponse(false, 'Player is offline.');
        }

        $response = self::executeRoute(Server::getServerURL($status->serverName) . 'execute/revivePlayer', [
            'targetSource' => $status->serverId,
        ]);

        if ($response->status) {
            $response->message = 'Revived player.';
        }

        return $response;
    }

    /**
     * Updates tattoo data for a player
     *
     * @param Player $player
     * @param string $character_id
     * @return OPFWResponse
     */
    public static function updateTattoos(Player $player, string $character_id): OPFWResponse
    {
        $license = $player->license_identifier;

        $status = Player::getOnlineStatus($license, false);
        if (! $status->isOnline()) {
            return new OPFWResponse(true, 'Player is offline, no refresh needed.');
        }

        $response = self::executeRoute(Server::getServerURL($status->serverName) . 'execute/refreshTattoos', [
            'licenseIdentifier' => $license,
            'characterId'       => $character_id,
        ]);

        if ($response->status) {
            $response->message = 'Updated tattoo data for player.';
        }

        return $response;
    }

    /**
     * Updates character data for a player
     *
     * @param Player $player
     * @param string $character_id
     * @return OPFWResponse
     */
    public static function updateCharacter(Player $player, string $character_id): OPFWResponse
    {
        $license = $player->license_identifier;

        $status = Player::getOnlineStatus($license, false);
        if (! $status->isOnline()) {
            return OPFWResponse::didNotExecute();
        }

        $response = self::executeRoute(Server::getServerURL($status->serverName) . 'execute/refreshCharacter', [
            'licenseIdentifier' => $license,
            'characterId'       => $character_id,
        ]);

        if ($response->status) {
            $response->message = 'Updated character data for player.';
        }

        return $response;
    }

    /**
     * Updates an inventory if its laoded.
     *
     * @param string $serverIp
     * @param string $inventory
     * @return OPFWResponse
     */
    public static function refreshInventory(string $serverIp, string $inventory): OPFWResponse
    {
        $response = self::executeRoute($serverIp . 'execute/refreshInventory', [
            'inventoryName' => $inventory,
        ]);

        if ($response->status) {
            $response->message = 'Successfully refreshed inventory.';
        }

        return $response;
    }

    /**
     * Unloads someone's character
     *
     * @param string $staffLicenseIdentifier
     * @param Player $player
     * @param string $character_id
     * @param string $message
     * @return OPFWResponse
     */
    public static function unloadCharacter(string $staffLicenseIdentifier, Player $player, string $character_id, string $message): OPFWResponse
    {
        $license = $player->license_identifier;

        $status = Player::getOnlineStatus($license, false);
        if (! $status->isOnline()) {
            return new OPFWResponse(true, 'Player is offline, no unload needede.');
        }

        $response = self::executeRoute(Server::getServerURL($status->serverName) . 'execute/unloadCharacter', [
            'licenseIdentifier' => $license,
            'characterId'       => $character_id,
            'message'           => $message,
        ]);

        if ($response->status) {
            $response->message = 'Unloaded players character.';
        }

        return $response;
    }

    /**
     * Updates someones queue position
     *
     * @param string $serverIp
     * @param string $licenseIdentifier
     * @param int $targetPosition
     * @return OPFWResponse
     */
    public static function updateQueuePosition(string $serverIp, string $licenseIdentifier, int $targetPosition): OPFWResponse
    {
        return self::executeRoute($serverIp . 'execute/setQueuePosition', [
            'licenseIdentifier' => $licenseIdentifier,
            'targetPosition'    => $targetPosition,
        ], 'PATCH');
    }

    /**
     * Executes an op-fw route
     *
     * @param string $route
     * @param array $data
     * @param string $requestType
     * @param int $timeout
     * @return OPFWResponse
     */
    private static function executeRoute(string $route, array $data, string $requestType = 'POST', int $timeout = 10, bool $isText = false): OPFWResponse
    {
        $token = env('OP_FW_TOKEN');

        if (! $token) {
            return new OPFWResponse(false, 'Invalid OP-FW configuration.');
        }

        /*
        if (!HttpHelper::ping($route, 800)) {
            LoggingHelper::log(HttpHelper::lastError());
            LoggingHelper::log("Cancelled $requestType request to $route");

            return new OPFWResponse(false, 'Failed to connect to OP-FW server.');
        }
        */

        $result = null;

        $client = new Client(
            [
                'verify'          => false,
                'timeout'         => $timeout,
                'connect_timeout' => 2,
                'http_errors'     => false,
                'headers'         => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        $statusCode    = 0;
        $statusCodeInt = 0;

        LoggingHelper::log('Do ' . $requestType . ' to "' . $route . '"');
        LoggingHelper::log('Data: ' . json_encode($data));

        try {
            $res = $client->request($requestType, $route, [
                'query' => $data,
            ]);

            $response = (string) $res->getBody();

            $statusCodeInt = $res->getStatusCode();
            $statusCode    = $statusCodeInt . " " . $res->getReasonPhrase();
        } catch (Throwable $t) {
            $response = $t->getMessage();
        }

        $log = $response;

        if (empty($log)) {
            $log = '-empty-';
        }

        if (strlen($log) > 300) {
            $log = substr($log, 0, 150) . '...';
        }

        LoggingHelper::log($statusCode . ': ' . $log);

        if ($isText) {
            return new OPFWResponse(true, $response);
        }

        return self::parseResponse($response);
    }

    /**
     * @param string $response
     * @return OPFWResponse
     */
    public static function parseResponse(string $response): OPFWResponse
    {
        // Sometimes the server sends stupid json responses with invalid characters
        $response = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $response);

        $json = json_decode($response, true);

        $code = 0;

        if ($json && isset($json['statusCode'])) {
            $code     = intval($json['statusCode']);
            $category = floor(intval($json['statusCode']) / 100);

            switch (intval($json['statusCode'])) {
                case 401:
                    return new OPFWResponse(false, 'Invalid OP-FW configuration. Wrong token?');
                case 400:
                case 403:
                case 404:
                    return new OPFWResponse(false, ! empty($json['message']) ? $json['message'] : 'Unknown error');
            }

            if ($category === 2) {
                return new OPFWResponse(true, ! empty($json['message']) ? 'Success: ' . $json['message'] : 'Successfully executed route', $json['data'] ?? null);
            }

            return new OPFWResponse(false, 'Failed to execute route: "Unknown server response ' . $code . '"');
        }

        $error = json_last_error();

        if ($error !== JSON_ERROR_NONE) {
            return new OPFWResponse(false, 'Failed to execute route: "Invalid response json: ' . self::jsonErrorToString($error) . '"');
        }

        return new OPFWResponse(false, 'Failed to execute route: "Invalid server response ' . $code . '"');
    }

    private static function jsonErrorToString(int $error): string
    {
        return match ($error) {
            JSON_ERROR_NONE => 'No errors',
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            default => 'Unknown error'
        };
    }
}
