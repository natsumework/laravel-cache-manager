<?php

return [
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
//        'user' => [
//            'ttl' => 300, // seconds
//            'hotspot_protection' => true,
//            'penetrate_protection' => true,
//            'hotspot_protection_ttl' => 10, // seconds
//        ],
//        'active_users' => [
//            'ttl' => 60 * 60,
//            'hotspot_protection' => false,
//            'penetrate_protection' => false,
//        ],
//        'post' => []
    ],

];
