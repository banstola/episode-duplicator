<?php

use App\Http\Controllers\EpisodeDuplicateController;
use App\Http\Middleware\SecureApiKeyMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {

    return response()->json(
        [
            'status' => 'healthy',
            'app_version' => config('app.app_version'),
        ]
    );
});

Route::prefix('v1')
    ->middleware(SecureApiKeyMiddleware::class)
    ->group(
        function () {
            Route::post('/episode-duplicate/{episode_uuid}', [EpisodeDuplicateController::class, 'scheduleEpisodeDuplication']);
            Route::get('/episode-duplicate/{episode_uuid}/status', [EpisodeDuplicateController::class, 'getDuplicationStatus']);
            Route::delete('/episode-duplicate/{episode_uuid}', [EpisodeDuplicateController::class, 'cancelDuplication']);
        }
    );
