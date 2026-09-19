<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentWebhookRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Models\LiveEvent;
use App\Services\TicketService;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService
    ) {}

    public function reserveAccess(StoreTicketRequest $request, LiveEvent $event)
    {

        $key = $request->header('Idempotency-Key');
        abort_unless($key, 422, 'Idempotency-Key is required.');

        return livdotResponse(
            $this->ticketService->reserveViewerAccess(
                $event,
                $request->user(),
                $request->string('provider_reference')->toString(),
                $key
            ),
            201,
            'tickets',
            true,
            app('url')->current(),
            [],
            'tickets'
        );
    }

    public function confirmPayment(StorePaymentWebhookRequest $request)
    {


        return livdotResponse(
            $this->ticketService->confirmGatewayPayment(
                $request->string('provider_event_id')->toString(),
                $request->string('provider_reference')->toString()
            ),
            200,
            'payment-transactions',
            true,
            app('url')->current(),
            [],
        );
    }
}
