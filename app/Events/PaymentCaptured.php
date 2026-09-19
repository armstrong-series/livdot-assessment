<?php

namespace App\Events;

use App\Models\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;

class PaymentCaptured
{
    use Dispatchable;

    public function __construct(public PaymentTransaction $payment) {}
}
