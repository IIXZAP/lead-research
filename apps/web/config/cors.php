<?php

return [
    /*
     * Only these paths are CORS-aware at all. The internal API
     * (internal/*) is never meant to be called from a browser, so it's
     * deliberately excluded — cross-origin rules are irrelevant to a
     * server-to-server HMAC-signed call anyway.
     */
    'paths' => ['api/campaigns', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST'],

    /*
     * No wildcard. If this app ever gets a separately-hosted frontend,
     * list its exact origin(s) here via env — never '*', since Sanctum
     * stateful auth relies on the browser sending cookies, and a
     * wildcard origin combined with credentials is a real vulnerability.
     */
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
