<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or specify the paths that should be CORS accessible. This file returns
    | an array of options compatible with the Laravel CORS middleware.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Allow all HTTP methods
    'allowed_methods' => ['*'],

    // Do NOT use '*' here when using credentials. Explicitly list the frontend origins.
    'allowed_origins' => [
        'http://127.0.0.1:4200',
        'http://localhost:4200',
    ],

    'allowed_origins_patterns' => [],

    // Allow any headers
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Allow cookies and Authorization headers with cross-site requests
    'supports_credentials' => true,
];