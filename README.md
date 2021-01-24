# Easily manage your cache for laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/natsumework/laravel-cache-manager.svg?style=flat-square)](https://packagist.org/packages/natsumework/laravel-cache-manager)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/natsumework/laravel-cache-manager.svg?style=flat-square)](https://packagist.org/packages/natsumework/laravel-notification-mitake)

Allows you to easily manage the cache with config file. And provide a simple solution to solve the cache penetration and hotspot invalid.

## Contents

- [Installation](#installation)
- [Configuration](#configuration)
    - [General Configuration](#general-configuration)
    - [Hotspot Invalid Protection Configuration](#hotspot-invalid-protection-configuration)
    - [Penetrate Protection Configuration](#penetrate-protection-configuration)
    - [Types Configuration](#types-configuration)
- [Usage](#usage)
- [Changelog](#changelog)
- [Testing](#testing)
- [Security](#security)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)


## Installation

Install this package via composer:

```
composer require natsumework/laravel-cache-manager
```

Publish the configuration file to `config/cache-manager.php`:

```
php artisan vendor:publish --provider="Natsumework\CacheManager\CacheManagerServiceProvider"
```

## Configuration

`config/cache-manager.php`

### General Configuration

```
return [
    // ...

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the cache connection that gets used while
    | using this caching library.
    |
    | Before use the "store", you need to define your `Cache Stores` in config/cache.php
    |
    | @see https://laravel.com/docs/master/cache#configuration
    */

    'store' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Storage time
    |--------------------------------------------------------------------------
    |
    | Specifies that items will expire in a few seconds.
    | If set to null, items will be stored indefinitely.
    }
    */
    'ttl' => 600,

    // ...
];
```

### Hotspot Invalid Protection Configuration

```
return [
    // ...
  
    /*
    |--------------------------------------------------------------------------
    | Hotspot Invalid Protection
    |--------------------------------------------------------------------------
    |
    | When we set the cache, we usually set an expiration time for the cache.
    | After this time, the cache will be invalid.
    | For some hotspot data, when the cache fails, there will be a large number
    | of requests coming over, and then hit the database, which will cause the
    | database enormous pressure.
    |
    | Set "hotspot_protection" to `true` will use the "Mutex lock" to avoid
    | the problem of database corruption caused by the failure of hotspot data.
    |
    | You must confirm that the store you are using supports atomic-locks.
    | @see https://laravel.com/docs/master/cache#atomic-locks
    */
    'hotspot_protection' => false,

    /*
    |--------------------------------------------------------------------------
    | Hotspot Invalid Protection Lock Timeout
    |--------------------------------------------------------------------------
    |
    | Specifies that "Mutex lock" will be automatically released in a few seconds.
    |
    */
    'hotspot_protection_ttl' => 15,

    /*
    |--------------------------------------------------------------------------
    | Hotspot Invalid Protection Suffix
    |--------------------------------------------------------------------------
    |
    | When Hotspot Invalid Protection is enabled, `{type}:{index}:{hotspot_protection_suffix}`
    | will be used as the key of "Mutex lock".
    | You must specify a unique value and avoid using this value as the key for storing data,
    | otherwise the data may be overwritten due to duplicate keys.
    |
    */
    'hotspot_protection_suffix' => 'hotspot_protection',

    // ...
];
```

### Penetrate Protection Configuration

```
return [
    // ...

    /*
    |--------------------------------------------------------------------------
    | Penetrate Protection
    |--------------------------------------------------------------------------
    |
    | When the business system initiates a query, the query will first go to
    | the cache, if the cache does not exist, it will go to the database for query.
    | If no data is found in the database, the data will not be stored in the cache,
    | which will cause the query hit the database every time.
    |
    | When Penetrate protection is enabled, `null` data will be converted to `false`
    | and stored in the cache.
    */
    'penetrate_protection' => false,

    // ...
];
```

### Types Configuration

This configuration allows you to easily manage all caches here.  

##### Notes
+ Before you put item into the cache storage, you have to define the type first.

```
return [
    // ...

    /*
    |--------------------------------------------------------------------------
    | Cache Types
    |--------------------------------------------------------------------------
    | Before you put item into the cache storage, you have to define the type first.
    |
    | You can also define `ttl`, `hotspot_protection`, `penetrate_protection`,
    | `hotspot_protection_ttl` for each type. It takes precedence over a globally defined one.
    |
    */
    'types' => [
        'user' => [
            'ttl' => 300, // seconds
            'hotspot_protection' => true,
            'penetrate_protection' => true,
            'hotspot_protection_ttl' => 10, // seconds
        ],
        'active_users' => [
            'ttl' => 60 * 60,
            'hotspot_protection' => false,
            'penetrate_protection' => false,
        ],
        'post' => [],

        // ...
    ],

    // ...
];
```

## Usage

#### Retrieve & Store item

If the item does not exist in the cache, the Closure passed to
the remember method will be executed and its result will be placed in the cache.

```
use Natsumework\CacheManager\Facades\CacheManager;

// CacheManager::remember($type, $index = null, \Closure $callback = null)

// you must define type `user` in config/cache-manager.php
$user = CacheManager::remember('user', 1, function () {
    return User::find(1);
});
```

```
$data = CacheManager::remember('type', 999, function () {
    return null;
});

// If `penetration_protection` is enabled, $data === false
// If `penetration_protection` is not enabled, $data === null
```

#### After the item is updated, use the Cache-Aside Pattern to invalid the item

The `updated` method will remove item.

```
// CacheManager::updated($type, $index = null)

$user = User::find(1);
$user->name = 'New name';
$user->save();

// user updated
CacheManager::updated('user', 1);
```

You can also use the `forget` method to remove item.

```
CacheManager::forget('user', 1);
```

#### Retrieve item

```
use Natsumework\CacheManager\Facades\CacheManager;

// CacheManager::get($type, $index = null, $default = null)

// you must define type `user` in config/cache-manager.php
$user = CacheManager::get('user', 1, 'user not found');
```

#### Storing Items In The Cache

```
use Natsumework\CacheManager\Facades\CacheManager;

// CacheManager::put($type, $index = null, $value = null)

// you must define type `user` in config/cache-manager.php
CacheManager::put('user', 1, User::find(1));
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email natsumework0902@gmail.com instead of using the issue tracker.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [natsumework](https://github.com/natsumework)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
