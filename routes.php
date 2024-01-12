<?php

use Illuminate\Support\Facades\Route;
use App\Services\Wisp\Service;
use App\Http\Middleware\ThrottleRequests;

Route::prefix('/service/{order}/wisp-server')->group(function () {    
    // Apply the custom rate limiter to all routes within the group.
    Route::middleware('throttle:wisp-power-actions')->group(function () {
        Route::get('/power/{action}', [Service::class, 'powerAction'])->name('wisp.server.power');
    });
});