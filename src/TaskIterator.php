<?php

namespace TTBooking\TaskScheduling;

use Generator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use IteratorAggregate;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use TTBooking\TaskScheduling\Contracts\Task;

/**
 * @implements IteratorAggregate<Task>
 */
class TaskIterator implements IteratorAggregate
{
    public function __construct(protected Application $app) {}

    /**
     * @return Generator<Task>
     */
    public function getIterator(): Generator
    {
        $staleCacheNotified = false;

        foreach ($this->getTasks() as $taskClass) {
            if (! class_exists($taskClass)) {
                if (! $staleCacheNotified) {
                    app('log')->warning('Task cache is outdated. Please re-cache by executing `artisan task:cache` command.');
                    $staleCacheNotified = true;
                }

                continue;
            }

            /** @var Task $task */
            $task = $this->app->make($taskClass);

            yield $task;
        }
    }

    /**
     * @return list<class-string<Task>>
     */
    public function getTasks(): array
    {
        return $this->getCachedTasks() ?? iterator_to_array($this->discoverTasks());
    }

    /**
     * @return list<class-string<Task>>|null
     */
    protected function getCachedTasks(): ?array
    {
        $cachedTasksPath = $this->cachePath('tasks.php');

        if (! file_exists($cachedTasksPath)) {
            return null;
        }

        return require $cachedTasksPath;
    }

    /**
     * @return Generator<class-string<Task>>
     */
    protected function discoverTasks(): Generator
    {
        /** @var iterable<SplFileInfo> $tasks */
        $tasks = (new Finder)->in($this->paths())->files();

        foreach ($tasks as $task) {
            $task = $this->app->getNamespace().str_replace(
                ['/', '.php'], ['\\', ''],
                Str::after($task->getRealPath(), realpath($this->appPath()).DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($task, Task::class) && ! (new ReflectionClass($task))->isAbstract()) {
                yield $task;
            }
        }
    }

    protected function appPath(string $path = ''): string
    {
        /** @var string $appPath */
        $appPath = method_exists($this->app, 'path')
            ? $this->app->path()
            : config('task-scheduling.app_path') ?? $this->app->basePath('app');

        return $appPath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * @return list<string>
     */
    protected function paths(): array
    {
        /** @var string|string[] $paths */
        $paths = config('task-scheduling.paths', []);

        return array_filter(
            array_unique(Arr::wrap($paths)),
            fn (string $path) => is_dir($path)
        );
    }

    public function cachePath(string $path = ''): string
    {
        /** @var string $cachePath */
        $cachePath = config('task-scheduling.cache_path') ?? $this->app->bootstrapPath('cache');

        return $cachePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
