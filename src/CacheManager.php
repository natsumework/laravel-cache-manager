<?php


namespace Natsumework\CacheManager;


use Illuminate\Support\Facades\Cache;
use Natsumework\CacheManager\Exceptions\TypeNotDefinedException;

class CacheManager
{
    private $store;

    private $ttl;

    private $types;

    private $penetrateProtection;

    private $hotspotProtection;

    private $hotspotProtectionTtl;

    private $hotspotProtectionSuffix;

    public function __construct(array $config)
    {
        $this->store = $config['store'];
        $this->ttl = $config['ttl'];
        $this->types = $config['types'];
        $this->penetrateProtection = $config['penetrate_protection'];
        $this->hotspotProtection = $config['hotspot_protection'];
        $this->hotspotProtectionTtl = $config['hotspot_protection_ttl'];
        $this->hotspotProtectionSuffix = $config['hotspot_protection_suffix'];
    }

    /**
     * @param $type
     * @param mixed $index
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed
     * @throws TypeNotDefinedException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($type, $index = null, $default = null)
    {
        return Cache::store($this->store)
            ->get(
                $this->getKey($type, $index),
                $default
            );
    }

    /**
     * @param $type
     * @param mixed $index
     * @param \Closure|null $callback
     * @return mixed
     * @throws TypeNotDefinedException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    public function remember($type, $index = null, \Closure $callback = null)
    {
        $value = $this->get($type, $index);

        if (!is_null($value)) {
            return $value;
        }

        if ($this->hotspotProtectionEnabled($type)) {
            $hotspotProtectionTtl = $this->getHotspotProtectionTtl($type);
            $lockKey = $this->getHotspotProtectionKey($type, $index);

            $lock = Cache::store($this->store)
                ->lock($lockKey, $hotspotProtectionTtl);

            try {
                $lock->block($hotspotProtectionTtl);

                $value = $this->get($type, $index);

                if (is_null($value)) {
                    $value = $callback();

                    $this->put($type, $index, $value);
                }
            } catch (\Throwable $e) {
                throw $e;
            } finally {
                $lock->release();
            }
        } else {
            $value = $callback();

            $this->put($type, $index, $value);
        }

        if ($this->penetrateProtectionEnabled($type) && is_null($value)) {
            $value = false;
        }

        return $value;
    }

    /**
     * @param $type
     * @param mixed $index
     * @param mixed $value
     * @return bool
     * @throws TypeNotDefinedException
     */
    public function put($type, $index = null, $value = null)
    {
        $key = $this->getKey($type, $index);
        $ttl = $this->getTtl($type);

        if ($this->penetrateProtectionEnabled($type) && is_null($value)) {
            $value = false;
        }

        return Cache::store($this->store)
            ->put($key, $value, $ttl);
    }

    /**
     * @param $type
     * @param mixed $index
     * @return bool
     * @throws TypeNotDefinedException
     */
    public function updated($type, $index = null)
    {
        return $this->forget($type, $index);
    }

    /**
     * @param $type
     * @param mixed $index
     * @return bool
     * @throws TypeNotDefinedException
     */
    public function forget($type, $index = null)
    {
        return Cache::store($this->store)
            ->forget($this->getKey($type, $index));
    }

    /**
     * @param string|null $store
     * @return CacheManager
     */
    public function store(?string $store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * @param int|null $ttl
     * @return CacheManager
     */
    public function ttl(?int $ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * @param $type
     * @param mixed $index
     * @return string
     * @throws TypeNotDefinedException
     */
    private function getKey($type, $index = null)
    {
        if (!array_key_exists($type, $this->types)) {
            throw new TypeNotDefinedException('Type ' . $type . ' not defined in cache-manager config...');
        }

        $key = $type . (is_null($index) ? '' : ':' . $index);

        return $key;
    }

    /**
     * @param $type
     * @return int|null
     */
    private function getTtl($type)
    {
        $ttl = $this->ttl;
        $setting = $this->types[$type];

        if (array_key_exists('ttl', $setting)) {
            $ttl = $setting['ttl'];
        }

        return $ttl;
    }

    /**
     * @param $type
     * @return boolean
     */
    private function penetrateProtectionEnabled($type)
    {
        $penetrateProtection = $this->penetrateProtection;
        $setting = $this->types[$type];

        if (array_key_exists('penetrate_protection', $setting)) {
            $penetrateProtection = $setting['penetrate_protection'];
        }

        return $penetrateProtection;
    }

    /**
     * @param $type
     * @return boolean
     */
    private function hotspotProtectionEnabled($type)
    {
        $hotspotProtection = $this->hotspotProtection;
        $setting = $this->types[$type];

        if (array_key_exists('hotspot_protection', $setting)) {
            $hotspotProtection = $setting['hotspot_protection'];
        }

        return $hotspotProtection;
    }

    /**
     * @param $type
     * @return int
     */
    private function getHotspotProtectionTtl($type)
    {
        $hotspotProtectionTtl = $this->hotspotProtectionTtl;
        $setting = $this->types[$type];

        if (array_key_exists('hotspot_protection_ttl', $setting)) {
            $hotspotProtectionTtl = $setting['hotspot_protection_ttl'];
        }

        return $hotspotProtectionTtl;
    }

    /**
     * @param $type
     * @param mixed $index
     * @return string
     * @throws TypeNotDefinedException
     */
    private function getHotspotProtectionKey($type, $index = null)
    {
        return $this->getKey($type, $index) . ':' . $this->hotspotProtectionSuffix;
    }
}
