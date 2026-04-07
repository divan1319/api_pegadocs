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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * Con credenciales no se puede usar Access-Control-Allow-Origin: *.
     * En producción/staging: solo CORS_ALLOWED_ORIGINS (dominio real del SPA).
     * En local: mismos orígenes + patrones para localhost en cualquier puerto (Vite, etc.).
     */
    'allowed_origins' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
    ))),

    'allowed_origins_patterns' => env('APP_ENV') === 'local' ? [
        '#^https?://(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$#',
    ] : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),

];
