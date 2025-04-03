<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'make:task')]
class TaskMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new scheduled task class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Task';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        $stub = match (true) {
            $type = $this->option('type') => "/stubs/task.$type.stub",
            $this->option('isolated') => '/stubs/task.isolated.stub',
            default => '/stubs/task.stub',
        };

        return $this->resolveStubPath($stub);
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../..'.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Tasks';
    }

    /**
     * Get the console command options.
     *
     * @return list<array>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the task already exists'],
            ['isolated', 'i', InputOption::VALUE_NONE, 'Generate an isolated, running in separate process task class'],
            ['type', null, InputOption::VALUE_REQUIRED, 'Manually specify the task stub file to use'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $input->setOption('isolated', confirm('Should the task run in a separate process?', false));
    }
}
