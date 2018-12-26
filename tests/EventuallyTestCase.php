<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests;

use Carbon\Carbon;
use Orchestra\Testbench\TestCase;

abstract class EventuallyTestCase extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->withFactories(__DIR__.'/database/factories');

        // Define an exact date/time to be always returned
        Carbon::setTestNow('2012-06-14 15:03:03');
    }
}
