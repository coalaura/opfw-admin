<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class LogParser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:accessed {--cluster} {regex}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds and lists everyone that accessed a page matching a certain regex.';

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
        $cluster = $this->option("cluster");
        $regex   = $this->argument("regex");

        $regex = preg_replace("/[^\w_\-\/:$]|\$(?!$)/m", "", $regex);

        if (empty($regex)) {
            $this->error("Invalid regex");

            return 1;
        }

        if ($cluster && !preg_match("/^c\d+$/m", $cluster)) {
            $this->error("Invalid cluster");

            return 1;
        }

        if (!Str::endsWith($regex, "$")) {
            $regex .= "$";
        }

        $files = $cluster ? $cluster . "_*.log" : "*.log";

        chdir(storage_path("logs"));

        $grep = "grep -ish -A 1 \"$regex\" $files";

        $this->comment("- $grep");

        $entries = $this->processGrep(shell_exec($grep) ?? "");

        if (empty($entries)) {
            $this->error("No entries found.");

            return 0;
        }

        foreach ($entries as $entry) {
            $this->line(" $entry");
        }
    }

    private function processGrep(?string $output)
    {
        $lines = explode("\n", trim($output));

        $results = [];
        $entry = false;

        foreach ($lines as $line) {
            // [2024-02-29T23:56:33+00:00] [45.140.184.93] [GET    ] /players
            $matched = preg_match("/^\[([\w:+-]+?)] \[([\d.]+?)] \[(\w+?)\s*] (.+?)$/m", $line, $matches);

            if ($matched) {
                $entry = trim($line);

                // Re-format date (white)
                $entry = str_replace($matches[1], "\e[97m" . date("Y-m-d H:i:s e", strtotime($matches[1])) . "\e[0m", $entry);

                // Hide IP addresses (dark gray)
                $entry = str_replace($matches[2], "\e[90m...\e[39m", $entry);

                // Mute method (dark gray)
                $entry = str_replace($matches[3], "\e[90m" . $matches[3] . "\e[0m", $entry);

                continue;
            }

            // [LogMiddleware.php:33] l4dv94fjx9fiq27pajoixdln3wdleo -> ACCEPTED Laura
            $matched = preg_match("/-> ACCEPTED (.+?)$/m", $line, $matches);

            if ($matched) {
                if ($entry) {
                    // Highlight username (white)
                    $user = "\e[97m\e[1m" . $matches[1] . "\e[0m";

                    $results[] = $entry . " -> " . $user;
                }
            }

            $entry = false;
        }

        return $results;
    }
}
