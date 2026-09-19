<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::post('/host', [EventController::class, 'hostEvent']);
    Route::post('{event}/broadcast/live', [EventController::class, 'beginBroadcast']);
    Route::post('{event}/broadcast/complete', [EventController::class, 'finalizeBroadcast']);
});
