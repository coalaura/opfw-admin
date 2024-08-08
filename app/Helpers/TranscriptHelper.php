<?php

namespace App\Helpers;

use App\Warning;

class TranscriptHelper
{
    public static function ensureMessageTranscripts(Warning &$warning)
    {
        if (empty($warning->message)) return;

        // For some weird ass reason, tickettool now blocks bots using cloudflare, completely breaking this feature :(
        // if (true) return;

        /**
         * Old ticket-tool links
         * https://tickettool.xyz/direct?url=https://cdn.discordapp.com/...
         */
        $re = '/https:\/\/tickettool\.xyz\/direct\?url=(https:\/\/cdn\.discordapp\.com\/.+?\/(\d+)\/transcript-\w+-(\d+)\.html(\?[\w=%&]+)?)/m';

        $message = preg_replace_callback($re, function ($matches) {
            $url = $matches[0];
            $cdn = $matches[1];
            $msgId = $matches[2];
            $id = $matches[3];

            $path = TranscriptHelper::ensureTranscript($msgId, $id, $cdn);
            if (!$path) {
                return $url;
            }

            return url($path);
        }, $warning->message);

        /**
         * New ticket-tool links
         * https://tickettool.xyz/transcript/v1/...
         */
        $re = '/https:\/\/tickettool\.xyz\/transcript\/v1\/(\d+)\/(\d+)\/transcript-\w+-(\d+)\.html\/(\w+)\/(\w+)\/(\w+)/m';

        $message = preg_replace_callback($re, function ($matches) {
            $url = $matches[0];
            $msgId = $matches[2];
            $id = $matches[3];

            $chnId = $matches[1];
            $ex = $matches[4];
            $is = $matches[5];
            $hm = $matches[6];

            // https://cdn.discordapp.com/attachments/661252628507787265/1271223318430617611/transcript-closed-25017.html?ex=66b68e79&is=66b53cf9&hm=cc1ed8a152cd42bbef480cb5510fc3a272d0d193bc0c1748b16aad7efc5150a0&
            $cdn = "https://cdn.discordapp.com/attachments/$chnId/$msgId/transcript-closed-$id.html?ex=$ex&is=$is&hm=$hm&";

            $path = TranscriptHelper::ensureTranscript($msgId, $id, $cdn);
            if (!$path) {
                return $url;
            }

            return url($path);
        }, $warning->message);

        // Save the message if it was changed
        if ($message !== $warning->message) {
            $warning->message = $message;

            $warning->save();
        }
    }

    private static function ensureTranscript(int $msgId, int $id, string $cdn): ?string
    {
        $dir = public_path('/_transcripts/');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $hash = md5($id . '-' . $msgId);
        $hash = substr($hash, 0, 3) . substr($hash, -3);

        $relative = '/_transcripts/' . $id . '-' . $hash . '.html';
        $path = public_path($relative);

        if (file_exists($path)) {
            return $relative;
        }

        try {
            $data = HttpHelper::get($cdn);

            if (empty($data)) {
                return null;
            }

            file_put_contents($path, $data);
        } catch (\Exception $e) {
            return null;
        }

        return $relative;
    }

    private static function findStoredMessageTranscripts(string $message): array
    {
        if (empty($message)) {
            return [];
        }

        $host = preg_quote(url('/'), '/');

        $re = '/' . $host . '\/_transcripts\/(\d+(-[a-f0-9]+)?)\.html/m';

        preg_match_all($re, $message, $matches, PREG_SET_ORDER, 0);

        $ids = [];

        foreach ($matches as $match) {
            $id = $match[1];

            $ids[] = $id;
        }

        return $ids;
    }

    public static function garbageCollectTranscripts(string $messageBefore, string $messageAfter)
    {
        $idsBefore = TranscriptHelper::findStoredMessageTranscripts($messageBefore);
        $idsAfter = TranscriptHelper::findStoredMessageTranscripts($messageAfter);

        $ids = array_diff($idsBefore, $idsAfter);

        foreach ($ids as $id) {
            TranscriptHelper::unlinkTranscript($id);
        }
    }

    public static function unlinkMessageTranscripts(Warning $warning)
    {
        $ids = TranscriptHelper::findStoredMessageTranscripts($warning->message);

        foreach ($ids as $id) {
            TranscriptHelper::unlinkTranscript($id);
        }
    }

    private static function unlinkTranscript(string $id)
    {
        $path = '/_transcripts/' . $id . '.html';

        $existsDB = Warning::where('message', 'LIKE', '%' . $path . '%')->exists();

        if ($existsDB) {
            return;
        }

        $path = public_path($path);

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
