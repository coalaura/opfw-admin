<?php

namespace App\Helpers;

use App\Warning;

class TranscriptHelper
{
    public static function replaceMessageTranscripts(string &$message)
    {
        /**
         * Old ticket-tool links
         * https://tickettool.xyz/direct?url=https://cdn.discordapp.com/...
         */
        $re = '/https:\/\/tickettool\.xyz\/direct\?url=(https:\/\/cdn\.discordapp\.com\/.+?\/(\d+)\/transcript-\w+-(\d+)\.html(\?[\w=%&]+)?)/m';

        $message = preg_replace_callback($re, function ($matches) {
            return $matches[1];
        }, $message);

        /**
         * New ticket-tool links
         * https://tickettool.xyz/transcript/v1/...
         */
        $re = '/https:\/\/tickettool\.xyz\/transcript\/v1\/(\d+)\/(\d+)\/(transcript-\w+-\d+)\.html\/(\w+)\/(\w+)\/(\w+)/m';

        $message = preg_replace_callback($re, function ($matches) {
            $msgId = $matches[1];
            $attachId = $matches[2];
            $name = $matches[3];

            $ex = $matches[4];
            $is = $matches[5];
            $hm = $matches[6];

            // https://cdn.discordapp.com/attachments/661252628507787265/1271223318430617611/transcript-closed-25017.html?ex=66b68e79&is=66b53cf9&hm=cc1ed8a152cd42bbef480cb5510fc3a272d0d193bc0c1748b16aad7efc5150a0&
            return "https://cdn.discordapp.com/attachments/$msgId/$attachId/$name.html?ex=$ex&is=$is&hm=$hm&";
        }, $message);
    }
}
