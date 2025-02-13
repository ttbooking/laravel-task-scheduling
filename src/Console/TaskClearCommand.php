<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use TTBooking\TaskScheduling\TaskIterator;

#[AsCommand(name: 'task:clear')]
class TaskClearCommand extends Command
{
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
     */
    public function handle(TaskIterator $tasks): void
    {
        @unlink($tasks->cachePath('tasks.php'));

        $this->components->info('Cached tasks cleared!');
    }
}
