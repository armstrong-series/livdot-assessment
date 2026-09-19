<?php

use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::post('events/{event}/stream-incidents', [StreamController::class, 'recordFailure']);
