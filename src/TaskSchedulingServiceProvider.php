<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class TaskSchedulingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/task-scheduling.php' => $this->app->configPath('task-scheduling.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../stubs/task.stub' => $this->app->basePath('stubs/task.stub'),
            ], 'stub');
        }

        $this->commands([
            Console\TaskCacheCommand::class,
            Console\TaskClearCommand::class,
            Console\TaskDispatchCommand::class,
            Console\TaskMakeCommand::class,
            Console\TaskRunCommand::class,
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/task-scheduling.php', 'task-scheduling');

        if ($this->app->runningInConsole()) {
            /** @var array{connection: string, queue: string} $config */
            $config = [
                'connection' => config('task-scheduling.connection'),
                'queue' => config('task-scheduling.queue'),
            ];

            $this->callAfterResolving(Schedule::class, (new Scheduler)
                ->onConnection($config['connection'])
                ->onQueue($config['queue'])
                ->make(new TaskIterator($this->app))
            );
        }
    }
}
