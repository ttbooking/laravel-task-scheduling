<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use TTBooking\TaskScheduling\TaskIterator;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TaskCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'task:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Discover and cache the application's scheduled tasks";

    /**
     * Execute the console command.
     *
     * @param  TaskIterator  $tasks
     * @return void
     */
    public function handle(TaskIterator $tasks): void
    {
        $this->call('task:clear');

        file_put_contents(
            $tasks->cachePath('tasks.php'),
            '<?php return '.var_export($tasks->getTasks(), true).';'.PHP_EOL
        );

        $this->info('Tasks cached successfully!');
    }
}
