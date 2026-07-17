<?php

return [
    'shared_secret' => env('INTERNAL_SHARED_SECRET', ''),
    'signature_ttl_seconds' => (int) env('INTERNAL_SIGNATURE_TTL_SECONDS', 300),
    'research_agent_base_url' => env('RESEARCH_AGENT_BASE_URL', 'http://python-api:8000'),

    // Where the Python service should send callbacks — this is the Docker
    // network hostname (the nginx service in docker-compose.yml), NOT
    // APP_URL, which is the browser-facing address and unreachable from
    // inside another container.
    'internal_callback_base_url' => env('INTERNAL_CALLBACK_BASE_URL', 'http://nginx'),
];
