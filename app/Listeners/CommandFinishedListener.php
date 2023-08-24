<?php

namespace App\Listeners;

use Illuminate\Console\Events\CommandFinished;
use App\Helpers\CacheHelper;

class CommandFinishedListener
{
    /**
     * Handle the event.
     *
     * @param  CommandFinished $event
     * @return void
     */
    public function handle(CommandFinished $event)
    {
        if ($event->command !== "migrate") {
            return;
        }

        CacheHelper::forget("panel_update");

        $this->log("> After migration tasks completed. <");
    }

    private function log(string $message)
    {
        $line = sprintf("\033[%sm%s\033[m", "1;38;5;113", $message);

        echo $line . "\n";
    }
}
