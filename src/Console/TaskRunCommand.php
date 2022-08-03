<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'task:run')]
class TaskRunCommand extends TaskDispatchCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:run
                            {task : Task FQCN}
                            {params? : Task parameters in query string format}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'task:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute scheduled task immediately';

    /**
     * The flag that means task must run synchronously.
     *
     * @var bool
     */
    protected bool $sync = true;
}
