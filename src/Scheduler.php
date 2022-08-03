<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling;

use Closure;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use ReflectionException, ReflectionMethod;
use TTBooking\TaskScheduling\Contracts\Task;

class Scheduler
{
    public function __construct(protected ?string $connection = null, protected ?string $queue = null)
    {
    }

    /**
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection(?string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue(?string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @param  iterable<Task>  $tasks
     * @return Closure
     */
    public function make(iterable $tasks): Closure
    {
        return function (Schedule $schedule) use ($tasks) {
            foreach ($tasks as $task) {
                $this->isTaskEnabled($task) && $this->multiSchedule($schedule, $task);
            }
        };
    }

    /**
     * @param  Task  $task
     * @return bool
     */
    protected function isTaskEnabled(Task $task): bool
    {
        return ! method_exists($task, 'isEnabled') || $task->isEnabled();
    }

    /**
     * Schedule task execution.
     *
     * @param  Schedule  $schedule
     * @param  Task  $task
     * @return void
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
     * @param  Schedule  $schedule
     * @param  Task  $task
     * @param  int  $instances
     * @return CallbackEvent[]
     */
    protected function eventFactory(Schedule $schedule, Task $task, int $instances = 1): array
    {
        return array_map(
            fn (Task $task) => $schedule->job(
                $task, $task->queue ?? $this->queue, $task->connection ?? $this->connection
            ),
            array_fill(0, $instances, $task)
        );
    }
}
