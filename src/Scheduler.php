<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling;

use Closure;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use ReflectionException;
use ReflectionMethod;
use TTBooking\TaskScheduling\Contracts\Task;

class Scheduler
{
    public function __construct(protected ?string $connection = null, protected ?string $queue = null) {}

    /**
     * @return $this
     */
    public function onConnection(?string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return $this
     */
    public function onQueue(?string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @param  iterable<Task>  $tasks
     */
    public function make(iterable $tasks): Closure
    {
        return function (Schedule $schedule) use ($tasks) {
            foreach ($tasks as $task) {
                $this->isTaskEnabled($task) && $this->multiSchedule($schedule, $task);
            }
        };
    }

    protected function isTaskEnabled(Task $task): bool
    {
        return ! method_exists($task, 'isEnabled') || $task->isEnabled();
    }

    protected function isTaskIsolated(Task $task): bool
    {
        return method_exists($task, 'isIsolated') && $task->isIsolated();
    }

    /**
     * Schedule task execution.
     */
    protected function multiSchedule(Schedule $schedule, Task $task): void
    {
        try {
            $scheduleMethod = new ReflectionMethod($task, 'schedule');
            $argc = $scheduleMethod->getNumberOfParameters();
            $argv = $this->eventFactory($schedule, $task, $argc);
            $scheduleMethod->invokeArgs($task, $argv);
        } catch (ReflectionException) {
        }
    }

    /**
     * Instantiate needed number of schedule events for the given task.
     *
     * @return list<Event>
     */
    protected function eventFactory(Schedule $schedule, Task $task, int $instances = 1): array
    {
        return array_map(
            $this->isTaskIsolated($task)
                ? fn (Task $task) => $schedule->command('task:run '.$task::class)
                : fn (Task $task) => $schedule->job(
                    $task, $task->queue ?? $this->queue, $task->connection ?? $this->connection
                ),
            array_fill(0, $instances, $task)
        );
    }
}
