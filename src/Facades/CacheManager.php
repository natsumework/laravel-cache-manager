<?php


namespace Natsumework\CacheManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get($type, $index = null, $default = null)
 * @method static mixed remember($type, $index = null, \Closure $callback = null)
 * @method static bool put($type, $index = null, $value = null)
 * @method static bool updated($type, $index = null)
 * @method static bool forget($type, $index = null)
 * @method static store(?string $store)
 * @method static ttl(?int $ttl)
 *
 * @see \Natsumework\CacheManager\CacheManager
 */
class CacheManager extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Natsumework\CacheManager\CacheManager::class;
    }
}
