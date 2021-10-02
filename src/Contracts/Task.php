<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Contracts;

use Illuminate\Console\Scheduling\CallbackEvent;

/**
 * @method void handle Execute the task.
 * @method bool isEnabled Check if task is enabled or not.
 * @method void schedule(CallbackEvent $fire) Schedule task execution.
 */
interface Task
{
}
