<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Tests;

use Orchestra\Testbench\TestCase;

class BaseLaravelTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/migrations');
    }
}
