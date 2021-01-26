<?php


namespace Natsumework\CacheManager\Tests;


class RedisTest extends TestCase
{
    protected function getConfig()
    {
        $config = parent::getConfig();
        $config['store'] = 'redis';

        return $config;
    }
}
