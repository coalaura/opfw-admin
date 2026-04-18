<?php

namespace App\Helpers;

class ImageHelper
{
    public static function convertToWebP($data)
    {
        $gd = @imagecreatefromstring($data);
        if (!$gd) {
            return false;
        }

        if (!imageistruecolor($gd)) {
            imagepalettetotruecolor($gd);
        }

        ob_start();

        imagewebp($gd, null, 90);

        $data = ob_get_contents();

        ob_end_clean();

        imagedestroy($gd);

        return $data;
    }
}
