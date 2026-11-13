<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | When using Sanctum's SPA authentication, you need to specify the domains
    | that will be making requests to your API. These domains will be able to
    | send credentials (cookies) to your application and be authenticated.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '127.0.0.1:8000,localhost:8000')),

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    |
    | This is the guard that will be used to authenticate users when they are
    | accessing the API. You may change this to any guard you have defined.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes that an issued token should be
    | considered valid. By default, we will store the tokens for one year.
    |
    */

    'expiration' => 525600,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be applied to all tokens issued by Sanctum. You may
    | change this to meet the needs of your application or to make the
    | tokens more identifiable to your users.
    |
    */

    'prefix' => 'personal_access_token',

];
