<?php

use App\Http\Controllers\PayoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::post('events/{event}/payouts', [PayoutController::class, 'preparePayout']);
});
