<?php

namespace Techysavvy\DropShare\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Techysavvy\Core\CoreServiceProvider;
use Techysavvy\DropShare\DropShareServiceProvider;
use Techysavvy\Ui\UiServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The <x-brand::layout> component's @vite directive has no build
        // manifest inside Testbench's throwaway skeleton app; every Feature
        // test in this plugin renders that layout, so disable Vite globally.
        $this->withoutVite();
    }

    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            UiServiceProvider::class,
            DropShareServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => storage_path('app/private'),
        ]);

        // Pin cache/session to the 'array' driver so RateLimiter state and
        // session-flashed data are deterministic within a single test and
        // never leak between tests via a shared file/db-backed store.
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
    }
}
