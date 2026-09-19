<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('payments/webhook', [TicketController::class, 'confirmPayment']);
