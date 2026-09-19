<?php

namespace App\Actions;

use App\Models\IdempotencyKey;
use App\Models\LiveEvent;
use App\Models\PaymentTransaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseTicketAction
{
    public function execute(LiveEvent $event, User $viewer, string $reference, string $idempotencyKey): Ticket
    {
        return DB::transaction(function () use ($event, $viewer, $reference, $idempotencyKey): Ticket {
            $key = IdempotencyKey::firstOrCreate(
                ['key'       => $idempotencyKey],
                ['operation' => 'ticket.purchase']
            );
            if ($key->resource_id) {
                return Ticket::findOrFail($key->resource_id);
            }
            $event = LiveEvent::lockForUpdate()->findOrFail($event->id);
            abort_unless(
                in_array($event->status, ['scheduled', 'live'], true),
                422,
                'Tickets are not available for this event.'
            );
            $ticket = Ticket::firstOrCreate(
                [
                    'live_event_id' => $event->id,
                    'viewer_id'     => $viewer->id
                ],
                ['amount_kobo'  => $event->ticket_price_kobo]
            );
            PaymentTransaction::firstOrCreate(
                ['ticket_id' => $ticket->id],
                [
                    'provider'           => 'external',
                    'provider_reference' => $reference,
                    'amount_kobo'        => $ticket->amount_kobo
                ]
            );
            $key->update(
                ['resource_id' => $ticket->id]
            );

            return $ticket;
        });
    }
}
