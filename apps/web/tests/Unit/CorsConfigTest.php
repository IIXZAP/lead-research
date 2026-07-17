<?php

namespace Tests\Unit;

use Tests\TestCase;

class CorsConfigTest extends TestCase
{
    public function test_cors_never_allows_a_wildcard_origin_with_credentials(): void
    {
        $allowedOrigins = config('cors.allowed_origins');
        $supportsCredentials = config('cors.supports_credentials');

        $this->assertNotContains('*', $allowedOrigins);

        if ($supportsCredentials) {
            $this->assertNotContains('*', $allowedOrigins, 'A wildcard origin with credentials is a real vulnerability.');
        }
    }

    public function test_internal_api_paths_are_excluded_from_cors(): void
    {
        $paths = config('cors.paths');

        foreach ($paths as $path) {
            $this->assertStringNotContainsString('internal', $path);
        }
    }
}
