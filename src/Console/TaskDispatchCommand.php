<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
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
     * The flag that means task must run synchronously.
     *
     * @var bool
     */
    protected bool $sync = false;

    /**
     * Execute the console command.
     *
     * @param  Container  $container
     * @param  Repository  $config
     * @param  Dispatcher  $dispatcher
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    public function handle(Container $container, Repository $config, Dispatcher $dispatcher): void
    {
        $dispatcher->{$this->sync ? 'dispatchSync' : 'dispatch'}
            ($instance = $this->newTaskInstance($container, $config));

        [$task, $xed] = [$instance::class, $this->sync ? 'finished' : 'enqueued'];
        $this->info("Task <comment>[$task]</comment> successfully $xed!");
    }

    /**
     * @param  Container  $container
     * @param  Repository  $config
     * @return Task
     *
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    protected function newTaskInstance(Container $container, Repository $config): Task
    {
        /** @psalm-suppress MixedArgument */
        return $this->configureTaskInstance(
            $container->make($this->getTaskClass(), $this->getParameters()), $config
        );
    }

    /**
     * @param  Task  $instance
     * @param  Repository  $config
     * @return Task
     */
    protected function configureTaskInstance(Task $instance, Repository $config): Task
    {
        if (! $this->sync) {
            /** @psalm-suppress NoInterfaceProperties */
            method_exists($instance, 'onConnection') && $instance->onConnection(
                $this->option('connection') ?? $instance->connection ?? $config->get('task-scheduling.connection')
            );

            /** @psalm-suppress NoInterfaceProperties */
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
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $task = str_replace('/', '\\', $this->argument('task') ?? '');

        if (! class_exists($task)) {
            throw new InvalidArgumentException("Task [$task] not found.");
        }

        if (! is_subclass_of($task, Task::class)) {
            throw new InvalidArgumentException("Class [$task] must implement [".Task::class."] interface.");
        }

        return $task;
    }

    /**
     * @return array
     */
    protected function getParameters(): array
    {
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        parse_str($this->argument('params') ?? '', $params);

        return $params;
    }
}
