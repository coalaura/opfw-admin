<?php
namespace App\Helpers;

use App\Warning;
use Illuminate\Support\Str;

class DiscordAttachmentHelper
{
    const BlockedExtensions = [
        'php',
        'exe',
        'py',
        'bat',
        'sh',
        'cmd',
        'js',
        'css',
    ];

    const ImageExtensions = [
        'jpg',  // JPEG
        'jpeg', // JPEG
        'png',  // PNG
        'gif',  // GIF
        'bmp',  // BMP (since PHP 7.2)
        'webp', // WebP (since PHP 7.0 with GD version 2.1.0+)
        'wbmp', // WBMP (Wireless Bitmap)
        'xbm',  // XBM (X BitMap)
    ];

    const NonWebPImageExtensions = [
        'gif',
    ];

    public static function ensureMessageAttachments(Warning &$warning)
    {
        $message = $warning->message;

        if (empty($message)) {
            return;
        }

        TranscriptHelper::replaceMessageTranscripts($message);

        /**
         * https://cdn.discordapp.com/attachments/686056492377571497/1271225863366774794/transcript-closed-24005.html?ex=66b690d8&is=66b53f58&hm=9b5e12309339ab4c9533be731a54a88fadca6948f284d0cae0917842ffd53c10&
         * https://media.discordapp.net/attachments/1268305038456131634/1312859870411620403/image.png?ex=674e0788&is=674cb608&hm=953728c6dc4601a6c790e0c16843eeb2f6b1fd778e05af1e0b42cb2d49019093&=&format=webp&quality=lossless&width=1290&height=572
         */
        $re = '/https:\/\/(?:cdn\.discordapp\.com|media\.discordapp\.net)\/attachments\/\d+\/(\d+)\/([^\s\/?#]+)\?([^\s#]+)/m';

        $message = preg_replace_callback($re, function ($matches) {
            $url      = $matches[0];
            $attachId = $matches[1];
            $name     = $matches[2];

            // Remove width and height
            $url = preg_replace('/&(width|height)=[0-9]+/', '', $url);

            if (! DiscordAttachmentHelper::isFileNameAllowed($name)) {
                return $url;
            }

            $ex = DiscordAttachmentHelper::find('/ex=([a-f0-9]+)/', $matches[3]);
            $is = DiscordAttachmentHelper::find('/is=([a-f0-9]+)/', $matches[3]);
            $hm = DiscordAttachmentHelper::find('/hm=([a-f0-9]+)/', $matches[3]);

            if (! $ex || ! $is || ! $hm) {
                return $url;
            }

            $ts = hexdec($ex);

            // Is link expired?
            if (! $ts || $ts < time()) {
                return $url;
            }

            $path = DiscordAttachmentHelper::ensureAttachment($attachId, $name, $url);
            if (! $path) {
                return $url;
            }

            LoggingHelper::log(sprintf('Downloaded attachment %s from %s', $path, $url));

            return url($path);
        }, $message);

        $otherRe = '/https:\/\/(?:i\.imgur\.com|imgur\.com|ibb\.co|files\.catbox\.moe|i\.postimg\.cc|postimg\.cc|live\.staticflickr\.com|i\.gyazo\.com|gyazo\.com)\/[^\s#"\'<>]+/m';

        $message = preg_replace_callback($otherRe, function ($matches) {
            $url         = $matches[0];
            $originalUrl = $url;
            $cleanUrl = $originalUrl;

            $url = rtrim($url, '.,;:)!?');

            $host = parse_url($url, PHP_URL_HOST);
            $path = parse_url($url, PHP_URL_PATH);

            if (! $host || ! $path) {
                return $cleanUrl;
            }

            if ($host === 'imgur.com') {
                if (! preg_match('/\.\w+$/', $path)) {
                    $url = 'https://i.imgur.com' . $path . '.png';
                } else {
                    $url = 'https://i.imgur.com' . $path;
                }
            } else if ($host === 'postimg.cc' || $host === 'ibb.co' || $host === 'gyazo.com') {
                $html = HttpHelper::get($url, "text/html");
                if ($html && preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $m)) {
                    $url = $m[1];

                    if ($url && Str::startsWith($url, "https://")) {
                        $cleanUrl = $url;
                    }
                } else {
                    return $cleanUrl;
                }
            }

            $name = basename(parse_url($url, PHP_URL_PATH));
            if (! preg_match('/\.\w+$/', $name)) {
                $name .= '.png';
            }

            if (! DiscordAttachmentHelper::isFileNameAllowed($name)) {
                return $cleanUrl;
            }

            $attachId = abs(crc32($originalUrl));

            $savedPath = DiscordAttachmentHelper::ensureAttachment($attachId, $name, $url);
            if (! $savedPath) {
                return $cleanUrl;
            }

            LoggingHelper::log(sprintf('Downloaded attachment %s from %s', $savedPath, $originalUrl));

            return url($savedPath);
        }, $message);

        // Update the message if it was changed
        if ($message !== $warning->message) {
            $warning->message = $message;

            $warning->save();
        }
    }

    private static function ensureAttachment(int $attachId, string $name, string $url): ?string
    {
        $dir = public_path('/_discord_attachments/');
        if (! file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $name = self::clean($name);

        $relative = '/_discord_attachments/' . $attachId . '-' . $name;
        $path     = public_path($relative);

        if (file_exists($path)) {
            return $relative;
        }

        try {
            $data = HttpHelper::get($url, "image/*");

            if (empty($data)) {
                LoggingHelper::log(sprintf('Failed to download attachment (empty data): %s', $url));

                return null;
            }

            $ext = pathinfo($name, PATHINFO_EXTENSION);

            if (in_array($ext, self::ImageExtensions) && ! in_array($ext, self::NonWebPImageExtensions)) {
                $relative = str_replace($ext, 'webp', $relative);
                $path     = public_path($relative);

                $data = ImageHelper::convertToWebP($data);

                if (! $data) {
                    LoggingHelper::log(sprintf('Failed to convert attachment to webp: %s', $url));

                    return null;
                }
            }

            file_put_contents($path, $data);
        } catch (\Exception $e) {
            LoggingHelper::log(sprintf('Failed to download attachment (%s): %s', $url, $e->getMessage()));

            return null;
        }

        return $relative;
    }

    private static function findStoredMessageAttachments(string $message): array
    {
        if (empty($message)) {
            return [];
        }

        $host = preg_quote(url('/'), '/');

        $re = '/' . $host . '\/_discord_attachments\/([a-f0-9]+)(\.\w+)/m';

        preg_match_all($re, $message, $matches, PREG_SET_ORDER, 0);

        $ids = [];

        foreach ($matches as $match) {
            $id  = $match[1];
            $ext = $match[2];

            $ids[] = $id . $ext;
        }

        return $ids;
    }

    public static function garbageCollectAttachments(string $messageBefore, string $messageAfter)
    {
        $namesBefore = self::findStoredMessageAttachments($messageBefore);
        $namesAfter  = self::findStoredMessageAttachments($messageAfter);

        $names = array_diff($namesBefore, $namesAfter);

        foreach ($names as $name) {
            self::unlinkAttachment($name);
        }
    }

    public static function unlinkMessageAttachments(Warning $warning)
    {
        $names = self::findStoredMessageAttachments($warning->message);

        foreach ($names as $name) {
            self::unlinkAttachment($name);
        }
    }

    private static function unlinkAttachment(string $name)
    {
        $path = '/_discord_attachments/' . $name;

        $existsDB = Warning::where('message', 'LIKE', '%' . $path . '%')->exists();

        if ($existsDB) {
            return;
        }

        $path = public_path($path);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    private static function find(string $regex, string $subject): ?string
    {
        preg_match($regex, $subject, $matches);

        if (sizeof($matches) < 2) {
            return null;
        }

        return $matches[1];
    }

    private static function clean(string $subject): string
    {
        $subject = preg_replace('/[^a-z0-9_\.-]+/i', '', $subject);

        $ext = pathinfo($subject, PATHINFO_EXTENSION);

        if (strlen($subject) - strlen($ext) > 28) {
            $subject = substr($subject, 0, 28) . '.' . $ext;
        }

        return $subject;
    }

    private static function isFileNameAllowed(string $name): bool
    {
        $ext = self::find('/\.(\w+)$/', $name);

        $ext = $ext ? strtolower($ext) : null;

        if (! $ext || in_array($ext, self::BlockedExtensions)) {
            return false;
        }

        // for html, only transcripts are allowed
        if ($ext === "html") {
            return ! ! preg_match("/^transcript-closed-\d+\.html$/", $name);
        }

        return true;
    }
}
