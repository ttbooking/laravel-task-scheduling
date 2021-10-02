<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use TTBooking\TaskScheduling\Concerns\TaskDiscovery;

class TaskCacheCommand extends Command
{
    use TaskDiscovery;

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
     * @return void
     */
    public function handle(): void
    {
        $this->call('task:clear');

        file_put_contents(
            $this->cachePath('tasks.php'),
            '<?php return '.var_export($this->getTasks(), true).';'.PHP_EOL
        );

        $this->info('Tasks cached successfully!');
    }

    /**
     * @return array
     */
    protected function getTasks(): array
    {
        return iterator_to_array($this->discoverTasks());
    }
}
