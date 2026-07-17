<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Laravel 11's default skeleton ships this class empty. authorizeResource()
 * (used by CampaignController) needs two things Laravel 11 no longer wires
 * up automatically: the AuthorizesRequests trait for the method itself, and
 * Illuminate\Routing\Controller as the base class for middleware(), which
 * authorizeResource() calls internally to register the `can` middleware.
 * The trait alone is not sufficient — see laravel/framework#50673.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
