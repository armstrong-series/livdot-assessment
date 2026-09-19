<?php

namespace App\Actions;

use App\Events\StreamFailureReported;
use App\Models\LedgerEntry;
use App\Models\LiveEvent;
use App\Models\Refund;
use App\Models\StreamIncident;
use Illuminate\Support\Facades\DB;
use App\Enums\EventStatus;
use App\Enums\TicketStatus;
use App\Enums\LedgerEntryType;

class ReportStreamFailureAction
{
    public function execute(LiveEvent $event): StreamIncident
    {
        $incident = DB::transaction(function () use ($event): StreamIncident {
            $event = LiveEvent::lockForUpdate()->findOrFail($event->id);
            abort_unless(
                $event->status === EventStatus::LIVE->value && $event->started_at,
                422,
                'Only live events can report stream failures.'
            );

            $failedAt = now();
            $eligible = $event->started_at->diffInSeconds($failedAt)
                < (
                    $event->scheduled_duration_minutes
                    * 60
                    * config('livdot.refund_threshold_percent')
                    / 100
                );


            $incident = StreamIncident::create(
                [
                    'live_event_id' => $event->id,
                    'failed_at'     => $failedAt,
                    'automatic_refunds_eligible' => $eligible,
                ]
            );
            if ($eligible) {
                foreach ($event->tickets()->where('status', 'active')->lockForUpdate()->get() as $ticket) {
                    $refund = Refund::firstOrCreate(
                        ['ticket_id' => $ticket->id],
                        [
                            'stream_incident_id' => $incident->id,
                            'amount_kobo'        => $ticket->amount_kobo,
                            'reason'             => 'stream_failed_before_threshold',
                        ]
                    );
                    $ticket->update(
                        [
                            'status'     => TicketStatus::REVOKED->value,
                            'revoked_at' => $failedAt,
                        ]
                    );
                    LedgerEntry::firstOrCreate(
                        [
                            'reference_type' => 'refund',
                            'reference_id'   => $refund->id,
                            'entry_type'     => LedgerEntryType::REFUND_RESERVE->value,
                        ],
                        [
                            'live_event_id' =>  $event->id,
                            'amount_kobo'    => $refund->amount_kobo
                        ]
                    );
                }
            }

            return $incident;
        });
        StreamFailureReported::dispatch(
            $incident
        );

        return $incident;
    }
}
