<?php

namespace App\Services;

use App\Actions\CapturePaymentAction;
use App\Actions\PurchaseTicketAction;
use App\Models\LiveEvent;
use App\Models\PaymentTransaction;
use App\Models\Ticket;
use App\Models\User;

class TicketService
{
    public function __construct(
        private PurchaseTicketAction $purchaseTicket,
        private CapturePaymentAction $capturePayment
    ) {}

    public function reserveViewerAccess(LiveEvent $event, User $viewer, string $reference, string $idempotencyKey): Ticket
    {
        return $this->purchaseTicket->execute(
            $event,
            $viewer,
            $reference,
            $idempotencyKey
        );
    }

    public function confirmGatewayPayment(string $providerEventId, string $reference): PaymentTransaction
    {
        return $this->capturePayment->execute(
            $providerEventId,
            $reference
        );
    }
}
