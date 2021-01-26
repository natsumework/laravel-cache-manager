<?php


namespace Natsumework\CacheManager\Tests;

class ArrayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $version = (int)substr(app()->version(), 0, 1);

        if ($version < 7) {
            // laravel 6 doesn't support array as cache driver
            $this->markTestSkipped(app()->version());
        }
    }

    protected function getConfig()
    {
        $config = parent::getConfig();
        $config['store'] = 'array';

        return $config;
    }
}
