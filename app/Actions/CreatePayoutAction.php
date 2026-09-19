<?php

namespace App\Actions;

use App\Models\LedgerEntry;
use App\Models\LiveEvent;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;
use App\Enums\EventStatus;
use App\Enums\LedgerEntryType;

class CreatePayoutAction
{
    public function execute(LiveEvent $event): Payout
    {
        return DB::transaction(function () use ($event): Payout {
            $event = LiveEvent::lockForUpdate()->findOrFail($event->id);
            abort_unless(
                $event->status === EventStatus::COMPLETED->value,
                422,
                'Only completed events can be paid out.'
            );
            abort_if(
                $event->tickets()->where('status', 'active')
                    ->whereHas('liveEvent.streamIncidents', fn() => null)->exists(),
                422
            );


            $sales = LedgerEntry::where('live_event_id', $event->id)
                ->where('entry_type', LedgerEntryType::TICKET_SALE->value)
                ->sum('amount_kobo');

            $refunds = LedgerEntry::where('live_event_id', $event->id)
                ->where('entry_type', LedgerEntryType::REFUND_RESERVE->value)
                ->sum('amount_kobo');


            $amount = $sales - $refunds;


            abort_if(
                $amount < 1,
                422,
                'No payable balance exists.'
            );

            return Payout::firstOrCreate(
                [
                    'live_event_id' => $event->id
                ],
                ['amount_kobo'      => $amount]
            );
        });
    }
}
