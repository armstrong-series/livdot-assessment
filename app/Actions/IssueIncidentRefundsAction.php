<?php

namespace App\Actions;


use App\Enums\LedgerEntryType;
use App\Enums\TicketStatus;
use App\Models\LedgerEntry;
use App\Models\Refund;
use App\Models\StreamIncident;
use Illuminate\Support\Facades\DB;


class IssueIncidentRefundsAction
{

    public function execute(StreamIncident $incident): void
    {
        DB::transaction(function () use ($incident): void {
            $incident = StreamIncident::lockForUpdate()
                ->with('liveEvent')
                ->findOrFail($incident->id);

            abort_unless(
                $incident->automatic_refunds_eligible,
                422,
                'Incident is not eligible for automatic refunds.'
            );

            $event = $incident->liveEvent;

            $tickets = $event->tickets()
                ->where('status', TicketStatus::ACTIVE->value)
                ->lockForUpdate()
                ->get();

            foreach ($tickets as $ticket) {
                $refund = Refund::firstOrCreate(
                    [
                        'ticket_id'          => $ticket->id,
                        'stream_incident_id' => $incident->id,
                    ],
                    [
                        'amount_kobo' => $ticket->amount_kobo,
                        'reason'      => 'stream_failed_before_threshold',
                    ]
                );

                $ticket->update(
                    [
                        'status'     => TicketStatus::REVOKED->value,
                        'revoked_at' => $incident->failed_at,
                    ]
                );

                LedgerEntry::firstOrCreate(
                    [
                        'reference_type' => 'refund',
                        'reference_id'   => $refund->id,
                        'entry_type'     => LedgerEntryType::REFUND_RESERVE->value,
                    ],
                    [
                        'live_event_id' => $event->id,
                        'amount_kobo'   => $refund->amount_kobo,
                    ]
                );
            }
        });
    }
}
