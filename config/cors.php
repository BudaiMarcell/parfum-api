<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control which browsers are allowed to call the API from
    | other origins. In production, the frontend origin is read from the
    | FRONTEND_URL environment variable — an empty value means no third-party
    | origins are trusted (preflight fails). In local dev we fall back to a
    | small list of Vite / serve defaults so `npm run dev` still works.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Routes that must respond to CORS preflight. Keep the sanctum cookie
    // path included so SPA authentication still bootstraps.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // In prod, FRONTEND_URL is the one origin allowed (e.g.
    // https://buttercupperfumery.com). The array_filter keeps the list
    // tight when the env var is unset in tests/CI.
    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL'),
        // Dev-only fallbacks — harmless in production because Vite ports
        // shouldn't be reachable from the public internet.
        env('APP_ENV') === 'local' ? 'http://localhost:5173' : null,
        env('APP_ENV') === 'local' ? 'http://127.0.0.1:5173' : null,
        env('APP_ENV') === 'local' ? 'http://localhost:3000' : null,
    ])),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Required for cookie-based Sanctum SPA auth. Combined with a
    // specific allowed_origins list (never '*') this is safe.
    'supports_credentials' => true,

];
