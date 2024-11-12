<?php

namespace App\Helpers;

class Timer
{
    /**
     * The timer stack
     */
    private static array $stack = [];

    public static function start(?string $title = null)
    {
        self::$stack[] = [
            'title' => $title,
            'time' => round(microtime(true) * 1000)
        ];
    }

    public static function stop(): int
    {
        $self = array_pop(self::$stack);

        if (!$self) {
            return 0;
        }

        $took = round(microtime(true) * 1000) - $self['time'];

        if ($self['title']) {
            LoggingHelper::log($self['title'] . ' took ' . $took . 'ms.');
        }

        return $took;
    }
}
