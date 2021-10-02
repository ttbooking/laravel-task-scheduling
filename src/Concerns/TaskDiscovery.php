<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Concerns;

use Generator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use TTBooking\TaskScheduling\Contracts\Task;

trait TaskDiscovery
{
    /**
     * @return Generator<Task>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    protected function getTaskIterator(): Generator
    {
        $tasks = $this->getCachedTasks() ?? $this->discoverTasks();

        foreach ($tasks as $task) {
            yield $this->getLaravel()->make($task);
        }
    }

    /**
     * @return array<class-string<Task>>|null
     * @psalm-suppress MixedInferredReturnType
     */
    protected function getCachedTasks(): ?array
    {
        $cachedTasksPath = $this->cachePath('tasks.php');

        if (! file_exists($cachedTasksPath)) {
            return null;
        }

        /** @psalm-suppress MixedReturnStatement */
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
            $task = $this->getLaravel()->getNamespace().str_replace(
                ['/', '.php'], ['\\', ''],
                Str::after($task->getRealPath(), realpath($this->appPath()).DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($task, Task::class) && ! (new ReflectionClass($task))->isAbstract()) {
                yield $task;
            }
        }
    }

    /**
     * @param  string  $path
     * @return string
     */
    protected function appPath(string $path = ''): string
    {
        /** @var string $appPath */
        $appPath = method_exists($this->getLaravel(), 'path')
            ? $this->getLaravel()->path()
            : $this->getConfig('app_path') ?? $this->getLaravel()->basePath('app');

        return $appPath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * @return string[]
     * @psalm-suppress MixedReturnTypeCoercion
     */
    protected function paths(): array
    {
        /** @var string|string[] $paths */
        $paths = $this->getConfig('paths', []);

        return array_filter(
            array_unique(Arr::wrap($paths)),
            fn (string $path) => is_dir($path)
        );
    }

    /**
     * @param  string  $path
     * @return string
     */
    protected function cachePath(string $path = ''): string
    {
        /** @var string $cachePath */
        $cachePath = $this->getConfig('cache_path') ?? $this->getLaravel()->bootstrapPath('cache');

        return $cachePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        /** @var Repository $config */
        $config = $this->getLaravel()->make(Repository::class);

        return $config->get('task-scheduling.'.$key, $default);
    }

    /**
     * Get the Laravel application instance.
     *
     * @return Application
     */
    public function getLaravel(): Application
    {
        return $this->app ?? $this->laravel
            ?? throw new LogicException('Could not resolve application instance.');
    }
}
