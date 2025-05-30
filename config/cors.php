<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'docs',
        'api/documentation'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development origins
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        
        // Production origins (from environment)
        env('FRONTEND_URL', 'https://app.openasm.com'),
        env('FRONTEND_DOMAIN', 'https://openasm.com'),
    ],

    'allowed_origins_patterns' => [
        // Allow localhost with any port for development
        '/^http:\/\/localhost(:\d+)?$/',
        '/^http:\/\/127\.0\.0\.1(:\d+)?$/',
        
        // Allow staging/preview domains
        '/^https:\/\/.*\.vercel\.app$/',
        '/^https:\/\/.*\.netlify\.app$/',
        '/^https:\/\/.*-openasm\..*\.app$/',
    ],

    'allowed_headers' => [
        '*',
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'X-CSRF-TOKEN',
        'Accept',
        'Origin',
        'X-Api-Key',
    ],

    'exposed_headers' => [
        'X-Total-Count',
        'X-Page-Count',
        'Link',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
