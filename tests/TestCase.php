<?php

use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations in the testing environment
        $kernel = $this->app->make(ConsoleKernel::class);
        $kernel->call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        // Roll back migrations to keep tests isolated
        $kernel = $this->app->make(ConsoleKernel::class);
        $kernel->call('migrate:reset', ['--force' => true]);

        parent::tearDown();
    }
}
