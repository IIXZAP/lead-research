<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Internal\InternalCallbackController;
use App\Http\Resources\CampaignResource;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);

// Phase 3: a thin JSON view of the same data the Blade dashboard shows,
// scoped by CampaignService the same way as the web controller so the
// two never drift apart. Real API endpoints for leads/exports land in
// Phase 6.
Route::middleware('auth:sanctum')->get('/campaigns', function (Request $request, CampaignService $campaigns) {
    return CampaignResource::collection($campaigns->listFor($request->user()));
});

Route::prefix('internal')->middleware(['throttle:internal-api', 'internal.signed'])->group(function () {
    Route::post('/research-jobs/{job}/callback', [InternalCallbackController::class, 'store'])
        ->name('internal.callback');
});
