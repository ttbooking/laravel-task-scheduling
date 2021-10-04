<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use TTBooking\TaskScheduling\Contracts\Task;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TaskDispatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:dispatch
                            {task : Task FQCN}
                            {--connection= : Set the desired connection for the task}
                            {--queue= : Set the desired queue for the task}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch scheduled task onto queue';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function handle(Container $container, Dispatcher $dispatcher): void
    {
        /** @var string $task */
        $task = $this->argument('task');
        $connection = $this->option('connection');
        $queue = $this->option('queue');

        if (! class_exists($task)) {
            throw new InvalidArgumentException('Task not found.');
        }

        if (! is_subclass_of($task, Task::class)) {
            throw new InvalidArgumentException('Class must implement ['.Task::class.'] interface.');
        }

        /** @var Task $instance */
        $instance = $container->make($task);
        $connection && method_exists($instance, 'onConnection') && $instance->onConnection($connection);
        $queue && method_exists($instance, 'onQueue') && $instance->onQueue($queue);

        $dispatcher->dispatch($instance);
    }
}
