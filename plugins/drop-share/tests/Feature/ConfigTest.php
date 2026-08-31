<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Techysavvy\DropShare\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_default_config_values_are_merged(): void
    {
        $this->assertSame('local', config('drop-share.disk'));
        $this->assertSame(25600, config('drop-share.max_upload_kb'));
        $this->assertSame(24, config('drop-share.lifespan_hours'));
        $this->assertSame(5, config('drop-share.prune_interval_minutes'));
    }
}
