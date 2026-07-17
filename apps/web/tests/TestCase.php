<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Laravel is supposed to auto-disable CSRF when APP_ENV=testing, but
     * that detection depends on environment resolution order that isn't
     * reliable across every host/Docker setup. Disabling it explicitly
     * here removes that flakiness entirely for every test in the suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
