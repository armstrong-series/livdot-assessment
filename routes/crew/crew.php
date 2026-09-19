<?php

use App\Http\Controllers\CrewAssignmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {
    Route::post('events/{event}/crew-assignments', [CrewAssignmentController::class, 'assignCrew']);
    Route::post('assignments/{assignment}/accept', [CrewAssignmentController::class, 'confirmAvailability']);
});
