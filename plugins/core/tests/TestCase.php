<?php

namespace Techysavvy\Core\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Techysavvy\Core\CoreServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            foreach (glob($dir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
    }

    /**
     * Create a temp directory holding the given files and return its path.
     *
     * @param  array<string, string>  $files  file name => contents
     */
    protected function makeBundleDir(array $files): string
    {
        $dir = sys_get_temp_dir().'/core-assets-'.bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);
        $this->tempDirs[] = $dir;

        foreach ($files as $name => $contents) {
            file_put_contents($dir.'/'.$name, $contents);
        }

        return $dir;
    }
}
