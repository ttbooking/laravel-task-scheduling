<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use TTBooking\TaskScheduling\Concerns\TaskDiscovery;

class TaskClearCommand extends Command
{
    use TaskDiscovery;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'task:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the task cache file';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        @unlink($this->cachePath('tasks.php'));

        $this->info('Cached tasks cleared!');
    }
}
