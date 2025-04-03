<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use TTBooking\TaskScheduling\Contracts\Task;

#[AsCommand(name: 'task:dispatch')]
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
     * The flag that means task must run synchronously.
     */
    protected bool $sync = false;

    /**
     * Execute the console command.
     *
     *
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    public function handle(Container $container, Repository $config, Dispatcher $dispatcher): void
    {
        $dispatcher->{$this->sync ? 'dispatchSync' : 'dispatch'}
            ($instance = $this->newTaskInstance($container, $config));

        [$task, $xed] = [$instance::class, $this->sync ? 'finished' : 'enqueued'];
        $this->components->info("Task <comment>[$task]</comment> successfully $xed!");
    }

    /**
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    protected function newTaskInstance(Container $container, Repository $config): Task
    {
        /** @var Task $task */
        $task = $container->make($this->getTaskClass(), $this->getParameters());

        return $this->configureTaskInstance($task, $config);
    }

    protected function configureTaskInstance(Task $instance, Repository $config): Task
    {
        if (! $this->sync) {
            method_exists($instance, 'onConnection') && $instance->onConnection(
                $this->option('connection') ?? $instance->connection ?? $config->get('task-scheduling.connection')
            );

            method_exists($instance, 'onQueue') && $instance->onQueue(
                $this->option('queue') ?? $instance->queue ?? $config->get('task-scheduling.queue')
            );
        }

        return $instance;
    }

    /**
     * @return class-string<Task>
     *
     * @throws InvalidArgumentException
     */
    protected function getTaskClass(): string
    {
        if (! is_string($task = $this->argument('task'))) {
            throw new InvalidArgumentException('Argument "task" must be a valid class string.');
        }

        $task = str_replace('/', '\\', $task);

        if (! class_exists($task)) {
            throw new InvalidArgumentException("Task [$task] not found.");
        }

        if (! is_subclass_of($task, Task::class)) {
            throw new InvalidArgumentException("Class [$task] must implement [".Task::class.'] interface.');
        }

        return $task;
    }

    /**
     * @return array<string, string|string[]>
     *
     * @throws InvalidArgumentException
     */
    protected function getParameters(): array
    {
        if (! is_string($query = $this->argument('params') ?? '')) {
            throw new InvalidArgumentException('Argument "params" must be a valid query string.');
        }

        parse_str($query, $params);

        /** @var array<string, string|string[]> */
        return $params;
    }
}
