<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use TTBooking\TaskScheduling\TaskIterator;

#[AsCommand(name: 'task:cache')]
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
     */
    public function handle(TaskIterator $tasks): void
    {
        $this->callSilent('task:clear');

        file_put_contents(
            $tasks->cachePath('tasks.php'),
            '<?php return '.var_export($tasks->getTasks(), true).';'.PHP_EOL
        );

        $this->components->info('Tasks cached successfully!');
    }
}
