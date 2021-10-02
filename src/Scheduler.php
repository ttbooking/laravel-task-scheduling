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
    /**
     * @param  iterable<Task>  $tasks
     * @return Closure
     */
    public static function for(iterable $tasks): Closure
    {
        return function (Schedule $schedule) use ($tasks) {
            foreach ($tasks as $task) {
                static::isTaskEnabled($task) && static::multiSchedule($schedule, $task);
            }
        };
    }

    /**
     * @param  Task  $task
     * @return bool
     */
    protected static function isTaskEnabled(Task $task): bool
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
    protected static function multiSchedule(Schedule $schedule, Task $task): void
    {
        try {
            $scheduleMethod = new ReflectionMethod($task, 'schedule');
            $argc = $scheduleMethod->getNumberOfParameters();
            $argv = static::eventFactory($schedule, $task, $argc);
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
    protected static function eventFactory(Schedule $schedule, Task $task, int $instances = 1): array
    {
        return array_map([$schedule, 'job'], array_fill(0, $instances, $task));
    }
}
