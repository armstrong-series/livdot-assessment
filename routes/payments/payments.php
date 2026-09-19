<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('webhook', [TicketController::class, 'confirmPayment']);
