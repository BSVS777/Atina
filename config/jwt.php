<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Signing Secret
    |--------------------------------------------------------------------------
    |
    | HS256 shared secret used to sign and verify API access tokens. Must be
    | a long random string, distinct from APP_KEY, and never committed.
    | Generate one with: php -r "echo bin2hex(random_bytes(32));"
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Token Time To Live
    |--------------------------------------------------------------------------
    |
    | Minutes an issued access token remains valid before it must be
    | reissued via POST /api/auth/login.
    |
    */

    'ttl' => (int) env('JWT_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Token Issuer
    |--------------------------------------------------------------------------
    |
    | Value stored in the "iss" claim of every issued token.
    |
    */

    'issuer' => env('JWT_ISSUER', env('APP_NAME', 'Laravel')),

];
