<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Contracts;

use Illuminate\Console\Scheduling\Event;

/**
 * @method void handle() Execute the task.
 * @method bool isEnabled() Check if task is enabled or not.
 * @method bool isIsolated() Check if task is isolated or not.
 * @method void schedule(Event $fire) Schedule task execution.
 */
interface Task {}
