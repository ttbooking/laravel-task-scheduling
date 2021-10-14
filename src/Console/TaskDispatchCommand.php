<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Config\Repository;
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
                            {params? : Task parameters in query string format}
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
     * @param  Container  $container
     * @param  Repository  $config
     * @param  Dispatcher  $dispatcher
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function handle(Container $container, Repository $config, Dispatcher $dispatcher): void
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $task = str_replace('/', '\\', $this->argument('task') ?? '');

        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        parse_str($this->argument('params') ?? '', $params);

        if (! class_exists($task)) {
            throw new InvalidArgumentException("Task [$task] not found.");
        }

        if (! is_subclass_of($task, Task::class)) {
            throw new InvalidArgumentException("Class [$task] must implement [".Task::class."] interface.");
        }

        /** @var Task $instance */
        $instance = $container->make($task, $params);

        /** @psalm-suppress NoInterfaceProperties */
        method_exists($instance, 'onConnection') && $instance->onConnection(
            $this->option('connection') ?? $instance->connection ?? $config->get('task-scheduling.connection')
        );

        /** @psalm-suppress NoInterfaceProperties */
        method_exists($instance, 'onQueue') && $instance->onQueue(
            $this->option('queue') ?? $instance->queue ?? $config->get('task-scheduling.queue')
        );

        $dispatcher->dispatch($instance);

        $this->info("Task <comment>[$task]</comment> successfully enqueued!");
    }
}
