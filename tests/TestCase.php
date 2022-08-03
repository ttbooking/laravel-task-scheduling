<?php

declare(strict_types=1);

namespace TTBooking\TaskScheduling\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use TTBooking\TaskScheduling\TaskSchedulingServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [TaskSchedulingServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        //
    }
}
