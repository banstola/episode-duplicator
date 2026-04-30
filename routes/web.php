<?php

use App\Http\Controllers\DuplicateEpisodeController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {

    return response()->json(
        [
            'status' => 'healthy',
            'app_version' => config('app.app_version')
        ]
    );
});
