<?php

declare(strict_types=1);

return [
    /*
    | Auth0 Domain
    |--------------------------------------------------------------------------
    | Your Auth0 tenant domain (e.g., "tenant.auth0.com" or "tenant.eu.auth0.com")
    | This is used to construct the authorization, token, and other endpoint URLs
    */
    'domain' => env('AUTH0_DOMAIN'),

    /*
    | Auth0 Client ID
    |----------------
    | The client ID from your Auth0 application settings in the Auth0 dashboard
    | This uniquely identifies your application to Auth0 during authentication
    */
    'client_id' => env('AUTH0_CLIENT_ID'),

    /*
    | Auth0 Client Secret
    |---------------------
    | The client secret from your Auth0 application settings
    | Required for confidential clients to exchange authorization codes for tokens
    */
    'client_secret' => env('AUTH0_CLIENT_SECRET'),

    /*
    | OAuth2 Scopes
    |-------------
    | The scopes to request during authentication (default: "openid profile offline_access")
    | "openid" enables OpenID Connect authentication, "profile" retrieves user profile information,
    | and "offline_access" allows retrieving refresh tokens for renewing access tokens
    */
    'scope' => env('AUTH0_SCOPES', 'openid profile offline_access'),

    /*
    | JWKS Cache TTL
    |--------------------------------------------------------------------------
    | Time-to-live for caching the JSON Web Key Set (JWKS) used to verify ID tokens
    | Accepts a relative date string (e.g., "1 day", "12 hours") converted via strtotime()
    |
    | The JWKS (JSON Web Key Set) is a set of public keys provided by Auth0 that are used
    | to verify the signatures of JWTs (JSON Web Tokens) issued by your Auth0 tenant.
    | When a user logs in, Auth0 issues an ID token (a JWT) that must be verified to ensure
    | it hasn't been tampered with. The JWKS contains the public keys needed for this verification.
    */
    'keys' => [
        'ttl' => env('AUTH0_JWKS_CACHE_TTL', '1 day'),
    ],

    /*
    | Metadata Cache TTL
    |-------------------
    | Time-to-live for caching the OpenID Connect discovery metadata from Auth0
    | Accepts a relative date string (e.g., "1 day", "6 hours") converted via strtotime()
    |
    | OpenID Connect Discovery metadata is a standardized document (available at the
    | .well-known/openid-configuration endpoint) that contains information about your
    | Auth0 tenant's OpenID Connect configuration, including endpoints (authorization,
    | token, userinfo, JWKS, etc.), supported scopes, response types, and other OAuth 2.0
    | and OpenID Connect parameters. This metadata allows your application to dynamically
    | discover Auth0's endpoints without hardcoding them.
    */
    'metadata' => [
        'ttl' => env('AUTH0_METADATA_CACHE_TTL', '1 day'),
    ],

    /*
    | Token Storage Configuration
    |---------------------------
    | Configure where and how access/refresh tokens are stored and managed
    | Supports both cache (fast) and database (persistent) storage drivers
    */
    'tokens' => [
        /*
        | Token Storage Driver
        |-------------------
        | The storage driver to use: "cache" or "database"
        | "cache" provides faster access while "database" offers persistence across cache clears
        */
        'driver' => env('AUTH0_TOKEN_STORE_DRIVER', 'cache'),

        /*
        | Token Storage Prefix
        |--------------------
        | Prefix for token keys to avoid collisions in the cache/storage
        | Only applicable when using the cache driver for token storage
        */
        'prefix' => 'auth0_tokens:',

        /*
        | Token Refresh Buffer
        |-------------------
        | Time before token expiration to proactively refresh the access token
        | Accepts a relative date string (e.g., "5 minutes", "30 seconds") converted via strtotime()
        |
        | This setting helps prevent token expiration during active user sessions by refreshing
        | the access token before it actually expires. For example, if set to "5 minutes" and
        | a token expires at 10:00 AM, the system will attempt to refresh it at 9:55 AM.
        */
        'refresh_buffer' => env('AUTH0_TOKEN_REFRESH_BUFFER', '5 minutes'),

        /*
        | Token Storage Drivers
        |---------------------
        | Configuration for each available token storage driver
        | Choose the appropriate driver based on your application requirements
        */
        'stores' => [
            /*
            | Cache Store Configuration
            |-----------------------
            | Store tokens in Laravel's cache system for fast access and retrieval
            | Optionally specify a custom cache store (e.g., "redis", "memcached")
            */
            'cache' => [
                'driver' => 'cache',
                'store' => env('AUTH0_TOKEN_CACHE_STORE'),
            ],

            /*
            | Database Store Configuration
            |--------------------------
            | Persist tokens in a database table for long-term storage and reliability
            | Useful when you need tokens to survive cache flushes or for distributed systems
            */
            'database' => [
                'driver' => 'database',
                'connection' => env('AUTH0_TOKEN_DB_CONNECTION'),
                'table' => env('AUTH0_TOKEN_TABLE', 'auth0_tokens'),
                'user_id_column' => 'user_id',

                /*
                | Cache TTL
                |----------
                | Time-to-live for caching database queries to avoid repeated reads
                | Accepts a relative date string (e.g., "5 minutes", "300", "1 hour") converted via strtotime()
                |
                | When using the database store, queries are cached to improve performance.
                | This setting controls how long the database results are cached before being refreshed.
                */
                'cache_ttl' => env('AUTH0_TOKEN_DB_CACHE_TTL', '5 minutes'),
            ],
        ],
    ],

    /*
    | Queue Configuration
    |--------------------
    | Configure queue names for Auth0-related jobs
    | This allows you to separate Auth0 jobs into dedicated queues for better performance
    */
    'queues' => [
        /*
        | Token Refresh Queue
        |--------------------
        | The queue name for the token refresh job
        | Set to null to use the default queue
        */
        'token_refresh' => env('AUTH0_TOKEN_REFRESH_QUEUE'),
    ],

    /*
    | HTTP Configuration
    |-------------------
    | Configure HTTP client settings for Auth0 API requests
    | These settings apply to all HTTP requests made to Auth0 endpoints
    */
    'http' => [
        /*
        | Request Timeout
        |----------------
        | Maximum time (in seconds) to wait for a response from Auth0 endpoints
        | Helps prevent hanging requests from blocking your application
        */
        'timeout' => env('AUTH0_HTTP_TIMEOUT', 30),

        /*
        | Retry Configuration
        |--------------------
        | Number of times to retry failed HTTP requests due to network issues or server errors
        | Set to 0 to disable retries. Retries use exponential backoff between attempts.
        */
        'retry_times' => env('AUTH0_HTTP_RETRY_TIMES', 3),

        /*
        | Retry Sleep (milliseconds)
        |---------------------------
        | Base time in milliseconds to wait between retry attempts
        | The actual wait time increases exponentially with each retry
        */
        'retry_sleep' => env('AUTH0_HTTP_RETRY_SLEEP', 100),
    ],

    /*
    | Route Configuration
    |-------------------
    | Configure the routes used for Auth0 authentication flow
    | These routes handle the authorization redirect and callback processing
    */
    'routes' => [
        /*
        | Home URL
        |---------
        | The URL to redirect to after a successful authentication
        | Typically points to your Filament admin panel or application dashboard
        */
        'home' => '/admin',

        /*
        | Route Prefix
        |------------
        | The prefix for all Auth0 authentication routes
        | Routes will be registered at "/{prefix}/authorize" and "/{prefix}/callback"
        */
        'prefix' => 'auth0',

        /*
        | Route Paths
        |-----------
        | The path suffixes for the authorization and callback endpoints
        | Combined with the prefix to form the complete route URLs used in the OAuth flow
        */
        'paths' => [
            'authorize' => '/authorize',
            'callback' => '/callback',
        ],
    ],
];
