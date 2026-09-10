<?php

namespace Techysavvy\DocToMarkdown\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Techysavvy\Core\CoreServiceProvider;
use Techysavvy\DocToMarkdown\DocToMarkdownServiceProvider;
use Techysavvy\Ui\UiServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The <x-ui::layout> component's @vite directive has no build
        // manifest inside Testbench's throwaway skeleton app.
        $this->withoutVite();
    }

    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            UiServiceProvider::class,
            DocToMarkdownServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
    }
}
