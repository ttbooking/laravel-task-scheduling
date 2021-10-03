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

            $this->commands([
                Console\TaskCacheCommand::class,
                Console\TaskClearCommand::class,
                Console\TaskMakeCommand::class,
            ]);
        }
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
            $this->callAfterResolving(Schedule::class, Scheduler::for(new TaskIterator($this->app)));
        }
    }
}
