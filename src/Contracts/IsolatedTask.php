<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Contracts;

use Illuminate\Console\Scheduling\Event;

/**
 * @extends Task<Event>
 */
interface IsolatedTask extends Task {}
