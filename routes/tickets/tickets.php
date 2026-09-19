<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('events/{event}/tickets', [TicketController::class, 'reserveAccess']);
