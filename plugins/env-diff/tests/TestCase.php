<?php

namespace Techysavvy\EnvDiff\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Techysavvy\Core\CoreServiceProvider;
use Techysavvy\EnvDiff\EnvDiffServiceProvider;
use Techysavvy\Ui\UiServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // <x-ui::layout>'s @vite directive has no build manifest in Testbench.
        $this->withoutVite();
    }

    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            UiServiceProvider::class,
            EnvDiffServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
    }
}
