<?php

namespace App\Services;

use App\Actions\CreatePayoutAction;
use App\Models\LiveEvent;
use App\Models\Payout;

class PayoutService
{
    public function __construct(private CreatePayoutAction $createPayout) {}

    public function prepareHostPayout(LiveEvent $event): Payout
    {
        return $this->createPayout->execute($event);
    }
}
