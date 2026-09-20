<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\PaymentCaptured;

class PaymentCapturedListener implements ShouldQueue
{


    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 60];
    }



    public function handle(PaymentCaptured $event): void
    {
        $payment = $event->payment;

        $ticket = $payment->ticket()->with('liveEvent')->first();

        if (! $ticket) {
            return;
        }

        // Post-payment side effects belong here.
        // Examples:
        // - Send ticket confirmation email
        // - Send ticket/access notification
        // - Publish analytics event
        // - Notify the host about a ticket sale

        // Example:
        // $ticket->user->notify(new TicketPurchasedNotification($ticket));
    }
}
