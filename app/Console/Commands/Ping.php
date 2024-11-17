<?php

namespace App\Console\Commands;

use App\Helpers\HttpHelper;
use Illuminate\Console\Command;
use App\Session;

class Ping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ping {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping a url.';

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
        $this->info(CLUSTER . " Pinging...");

        if (HttpHelper::ping($this->argument('url'), 1000)) {
            $this->info(CLUSTER . " Pong!");
        } else {
            $this->warn(CLUSTER . " " . HttpHelper::lastError());
        }

        return 0;
    }
}
