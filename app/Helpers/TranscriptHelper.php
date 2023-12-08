<?php

namespace App\Helpers;

use App\Warning;

class TranscriptHelper
{
    public static function ensureMessageTranscripts(Warning &$warning)
    {
        if (empty($warning->message)) return;

        $re = '/https:\/\/tickettool\.xyz\/direct\?url=(https:\/\/cdn\.discordapp\.com\/.+?\/transcript-\w+-(\d+)\.html\?[\w=%&]+)/m';

        $message = preg_replace_callback($re, function ($matches) {
            $url = $matches[0];
            $cdn = $matches[1];
            $id = $matches[2];

            $path = TranscriptHelper::ensureTranscript($id, $cdn);
            if (!$path) {
                return $url;
            }

            return url($path);
        }, $warning->message);

        if ($message !== $warning->message) {
            $warning->message = $message;

            $warning->save();
        }
    }

    private static function ensureTranscript(int $id, string $cdn): ?string
    {
        $dir = public_path('/_transcripts/');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $relative = '/_transcripts/'. $id . '.html';
        $path = public_path($relative);

        if (file_exists($path)) {
            return $relative;
        }

        $data = file_get_contents($cdn);

        if (empty($data)) {
            return null;
        }

        file_put_contents($path, $data);

        return $relative;
    }

    private static function findStoredMessageTranscripts(string $message): array
    {
        if (empty($message)) {
            return [];
        }

        $host = preg_quote(url('/'), '/');

        $re = '/https:\/\/tickettool\.xyz\/direct\?url=' . $host . '\/_transcripts\/(\d+)\.html/m';

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

    private static function unlinkTranscript(int $id)
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
