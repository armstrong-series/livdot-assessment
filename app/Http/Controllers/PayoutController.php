<?php

namespace App\Http\Controllers;

use App\Models\LiveEvent;
use App\Services\PayoutService;

class PayoutController extends Controller
{
    public function __construct(private PayoutService $payoutService) {}

    public function preparePayout(LiveEvent $event)
    {
        abort_unless(
            $event->host_id === request()->user()->id || request()->user()->isAdmin(),
            403
        );

        return livdotResponse($this->payoutService->prepareHostPayout($event), 201, 'payouts');
    }
}
