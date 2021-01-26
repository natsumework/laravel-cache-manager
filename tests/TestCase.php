<?php

namespace Natsumework\CacheManager\Tests;

use Illuminate\Support\Facades\Cache;
use Natsumework\CacheManager\CacheManager;
use Natsumework\CacheManager\Exceptions\TypeNotDefinedException;
use Orchestra\Testbench\TestCase as BaseTestCase;
use ReflectionMethod;

abstract class TestCase extends BaseTestCase
{
    protected $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->cacheManager = new CacheManager($this->getConfig());
    }

    protected function getConfig()
    {
        return [
            'store' => 'array',
            'ttl' => 60,
            'hotspot_protection' => false,
            'hotspot_protection_ttl' => 15,
            'hotspot_protection_suffix' => 'hotspot_protection',
            'penetrate_protection' => false,
            'types' => [
                'empty_item_test' => [],
                'put_item_test' => [],
                'expire_after_three_second_item_test' => [
                    'ttl' => 3
                ],
                'updated_item_test' => [],
                'forgot_item_test' => [],
                'remember_item_test' => [],
                'penetrate_protection_item_test' => [
                    'penetrate_protection' => true,
                ],
                'hotspot_protection_item_test' => [
                    'hotspot_protection' => true,
                    'hotspot_protection_ttl' => 10,
                ],
                'hotspot_protection_key_test' => [],
                'cache_key_test' => [],
                'global_setting_test' => [],
                'local_setting_test' => [
                    'ttl' => 100,
                    'hotspot_protection' => true,
                    'hotspot_protection_ttl' => 30,
                    'penetrate_protection' => true,
                ]
            ],
        ];
    }

    public function test_type_not_defined_on_get_method()
    {
        $this->expectException(TypeNotDefinedException::class);

        $cacheManager = $this->cacheManager;

        $cacheManager->get('not defined type');
    }

    public function test_type_not_defined_on_remember_method()
    {
        $this->expectException(TypeNotDefinedException::class);

        $cacheManager = $this->cacheManager;

        $cacheManager->remember('not defined type', null, function () {
            return 'default';
        });
    }

    public function test_type_not_defined_on_updated_method()
    {
        $this->expectException(TypeNotDefinedException::class);

        $cacheManager = $this->cacheManager;

        $cacheManager->updated('not defined type');
    }

    public function test_get_empty_item()
    {
        $cacheManager = $this->cacheManager;

        $item = $cacheManager->get('empty_item_test');

        $this->assertNull($item);

        $item = $cacheManager->get('empty_item_test', 'index');

        $this->assertNull($item);

        $item = $cacheManager->get('empty_item_test', 999);

        $this->assertNull($item);
    }

    public function test_get_empty_item_with_default_value()
    {
        $cacheManager = $this->cacheManager;

        $item = $cacheManager->get('empty_item_test', null, 'default value');

        $this->assertNotNull($item);
        $this->assertSame('default value', $item);

        $item = $cacheManager->get('empty_item_test', null, 1);

        $this->assertNotNull($item);
        $this->assertSame(1, $item);
        $this->assertNotSame('1', $item);
    }

    public function test_put_item_into_cache()
    {
        $cacheManager = $this->cacheManager;

        // string value
        $index = time();
        $result = $cacheManager->put('put_item_test', $index, 'item value');
        $this->assertIsBool($result);

        $item = $cacheManager->get('put_item_test', $index);
        $this->assertSame('item value', $item);

        // array value
        $index += 1;

        $array = [
            'test' => 'value',
            'test2' => 10
        ];

        $cacheManager->put('put_item_test', $index, $array);
        $item = $cacheManager->get('put_item_test', $index);
        $this->assertSame($array, $item);

        // null value
        $index += 1;
        $cacheManager->put('put_item_test', $index, null);

        $item = $cacheManager->get('put_item_test', $index);
        $this->assertSame(null, $item);

        $item = $cacheManager->get('put_item_test', $index, 'default');
        $this->assertSame('default', $item);

        // `put` will use the new value even if the cache has not expired
        $index += 1;
        $cacheManager->put('put_item_test', $index, 'value');
        $item = $cacheManager->get('put_item_test', $index);
        $this->assertSame('value', $item);

        $cacheManager->put('put_item_test', $index, 'new value');
        $item = $cacheManager->get('put_item_test', $index);
        $this->assertSame('new value', $item);
    }

    public function test_remember_item_into_cache()
    {
        $cacheManager = $this->cacheManager;

        // string value
        $index = time();
        $result = $cacheManager->remember('remember_item_test', $index, function () {
            return 'item value';
        });
        $this->assertSame('item value', $result);

        $item = $cacheManager->get('remember_item_test', $index);
        $this->assertSame('item value', $item);

        // array value
        $index += 1;

        $array = [
            'test' => 'value',
            'test2' => 10
        ];

        $result = $cacheManager->remember('remember_item_test', $index, function () use ($array) {
            return $array;
        });
        $item = $cacheManager->get('remember_item_test', $index);
        $this->assertSame($array, $result);
        $this->assertSame($array, $item);

        // null value
        $index += 1;
        $result = $cacheManager->remember('remember_item_test', $index, function () {
            return null;
        });

        $item = $cacheManager->get('remember_item_test', $index);
        $this->assertSame(null, $result);
        $this->assertSame(null, $item);

        $item = $cacheManager->get('remember_item_test', $index, 'default');
        $this->assertSame('default', $item);

        // Even if the callback value is changed, `remember` will still return the value in the cache
        $index += 1;
        $result = $cacheManager->remember('remember_item_test', $index, function () {
            return 'value';
        });
        $this->assertSame('value', $result);

        $result = $cacheManager->remember('remember_item_test', $index, function () {
            return 'value edited';
        });
        $this->assertSame('value', $result);
    }

    public function test_item_will_expire_after_ttl()
    {
        $config = $this->getConfig();
        $cacheManager = $this->cacheManager;

        // put
        $cacheManager->put('expire_after_three_second_item_test', null, 'expire_after_three_second_item_put_value');

        $item = $cacheManager->get('expire_after_three_second_item_test');
        $this->assertSame('expire_after_three_second_item_put_value', $item);

        // expire after seconds
        sleep($config['types']['expire_after_three_second_item_test']['ttl'] + 1);
        $item = $cacheManager->get('expire_after_three_second_item_test');
        $this->assertSame(null, $item);

        // remember
        $value = $cacheManager->remember('expire_after_three_second_item_test', 1, function () {
            return 'expire_after_three_second_item_remember_value';
        });
        $this->assertSame('expire_after_three_second_item_remember_value', $value);

        $item = $cacheManager->get('expire_after_three_second_item_test', 1);
        $this->assertSame('expire_after_three_second_item_remember_value', $item);

        // expire after seconds
        sleep($config['types']['expire_after_three_second_item_test']['ttl'] + 1);
        $item = $cacheManager->get('expire_after_three_second_item_test', 1);
        $this->assertSame(null, $item);
    }

    public function test_item_updated()
    {
        $cacheManager = $this->cacheManager;

        $cacheManager->put('updated_item_test', 1, 'item value');
        $cacheManager->updated('updated_item_test', 1);
        $item = $cacheManager->get('updated_item_test', 1);
        $this->assertSame(null, $item);

        $cacheManager->put('updated_item_test', null, 'item value');
        $cacheManager->updated('updated_item_test');
        $item = $cacheManager->get('updated_item_test');
        $this->assertSame(null, $item);
    }

    public function test_forget_item()
    {
        $cacheManager = $this->cacheManager;

        $cacheManager->put('forgot_item_test', 1, 'item value');
        $cacheManager->forget('forgot_item_test', 1);
        $item = $cacheManager->get('forgot_item_test', 1);
        $this->assertSame(null, $item);

        $cacheManager->put('forgot_item_test', null, 'item value');
        $cacheManager->forget('forgot_item_test');
        $item = $cacheManager->get('forgot_item_test');
        $this->assertSame(null, $item);
    }

    public function test_penetrate_protection_enabled_item()
    {
        $cacheManager = $this->cacheManager;

        // put
        $index = time();
        $cacheManager->put('penetrate_protection_item_test', $index, null);
        $item = $cacheManager->get('penetrate_protection_item_test', $index);
        $this->assertSame(false, $item);

        // remember
        $index++;
        $item = $cacheManager->remember('penetrate_protection_item_test', $index, function () {
            return null;
        });
        $this->assertSame(false, $item);
    }

    public function test_hotspot_protection_enabled_item()
    {
        $cacheManager = $this->cacheManager;
        $config = $this->getConfig();

        // if hotspot protection is enabled, subsequent requests will be block
        $start = time();

        $cacheManager->remember('hotspot_protection_item_test', $start, function () use ($start, $config) {
            sleep(1); // prevent timeout

            $cacheManager = new CacheManager($config);
            $cacheManager->remember('hotspot_protection_item_test', $start, function () {
                return 2;
            });

            return 1;
        });

        $end = time();
        $this->assertGreaterThan(
            $config['types']['hotspot_protection_item_test']['hotspot_protection_ttl'] - 2,
            $end - $start
        );
    }

    public function test_hotspot_protection_locker_key()
    {
        $reflector = new ReflectionMethod(CacheManager::class, 'getHotspotProtectionKey');
        $reflector->setAccessible(true);

        $this->assertSame('hotspot_protection_key_test:hotspot_protection',
            $reflector->invoke(
                $this->cacheManager,
                'hotspot_protection_key_test'
            )
        );

        $this->assertSame('hotspot_protection_key_test:index:hotspot_protection',
            $reflector->invoke(
                $this->cacheManager,
                'hotspot_protection_key_test',
                'index'
            )
        );

        $this->assertSame('hotspot_protection_key_test:0:hotspot_protection',
            $reflector->invoke(
                $this->cacheManager,
                'hotspot_protection_key_test',
                0
            )
        );

        $this->assertSame('hotspot_protection_key_test::hotspot_protection',
            $reflector->invoke(
                $this->cacheManager,
                'hotspot_protection_key_test',
                ''
            )
        );
    }

    public function test_cache_key()
    {
        $reflector = new ReflectionMethod(CacheManager::class, 'getKey');
        $reflector->setAccessible(true);

        $this->assertSame('cache_key_test',
            $reflector->invoke(
                $this->cacheManager,
                'cache_key_test'
            )
        );

        $this->assertSame('cache_key_test:index',
            $reflector->invoke(
                $this->cacheManager,
                'cache_key_test',
                'index'
            )
        );

        $this->assertSame('cache_key_test:0',
            $reflector->invoke(
                $this->cacheManager,
                'cache_key_test',
                0
            )
        );

        $this->assertSame('cache_key_test:',
            $reflector->invoke(
                $this->cacheManager,
                'cache_key_test',
                ''
            )
        );
    }

    public function test_global_type_setting()
    {
        $config = $this->getConfig();

        // ttl
        $reflector = new ReflectionMethod(CacheManager::class, 'getTtl');
        $reflector->setAccessible(true);

        $this->assertSame($config['ttl'],
            $reflector->invoke(
                $this->cacheManager,
                'global_setting_test'
            )
        );

        // hotspot_protection
        $reflector = new ReflectionMethod(CacheManager::class, 'hotspotProtectionEnabled');
        $reflector->setAccessible(true);

        $this->assertSame($config['hotspot_protection'],
            $reflector->invoke(
                $this->cacheManager,
                'global_setting_test'
            )
        );

        // hotspot_protection_ttl
        $reflector = new ReflectionMethod(CacheManager::class, 'getHotspotProtectionTtl');
        $reflector->setAccessible(true);

        $this->assertSame($config['hotspot_protection_ttl'],
            $reflector->invoke(
                $this->cacheManager,
                'global_setting_test'
            )
        );

        // penetrate_protection
        $reflector = new ReflectionMethod(CacheManager::class, 'penetrateProtectionEnabled');
        $reflector->setAccessible(true);

        $this->assertSame($config['penetrate_protection'],
            $reflector->invoke(
                $this->cacheManager,
                'global_setting_test'
            )
        );
    }

    public function test_local_type_setting()
    {
        $config = $this->getConfig()['types']['local_setting_test'];

        // ttl
        $reflector = new ReflectionMethod(CacheManager::class, 'getTtl');
        $reflector->setAccessible(true);

        $this->assertSame($config['ttl'],
            $reflector->invoke(
                $this->cacheManager,
                'local_setting_test'
            )
        );

        // hotspot_protection
        $reflector = new ReflectionMethod(CacheManager::class, 'hotspotProtectionEnabled');
        $reflector->setAccessible(true);

        $this->assertSame($config['hotspot_protection'],
            $reflector->invoke(
                $this->cacheManager,
                'local_setting_test'
            )
        );

        // hotspot_protection_ttl
        $reflector = new ReflectionMethod(CacheManager::class, 'getHotspotProtectionTtl');
        $reflector->setAccessible(true);

        $this->assertSame($config['hotspot_protection_ttl'],
            $reflector->invoke(
                $this->cacheManager,
                'local_setting_test'
            )
        );

        // penetrate_protection
        $reflector = new ReflectionMethod(CacheManager::class, 'penetrateProtectionEnabled');
        $reflector->setAccessible(true);

        $this->assertSame($config['penetrate_protection'],
            $reflector->invoke(
                $this->cacheManager,
                'local_setting_test'
            )
        );
    }
}
