<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Session;

class ClearSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all laravel sessions.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info(CLUSTER . " Dropping all sessions...");

        $count = Session::query()->delete();

        $this->info(CLUSTER . " Dropped $count sessions.");

        return 0;
    }
}
