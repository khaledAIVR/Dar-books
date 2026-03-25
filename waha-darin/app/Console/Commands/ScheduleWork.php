<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Laravel versions < 8 don't include `schedule:work`.
 * This command emulates it by running `schedule:run` in a loop.
 */
class ScheduleWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:work {--sleep=60 : Seconds to sleep between runs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the scheduled commands continuously (dev helper).';

    public function handle()
    {
        $sleep = (int) $this->option('sleep');
        if ($sleep < 1) {
            $sleep = 1;
        }

        $this->info("Starting scheduler loop (sleep={$sleep}s). Press Ctrl+C to stop.");

        while (true) {
            try {
                $this->call('schedule:run');
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }

            sleep($sleep);
        }
    }
}

