<?php

namespace App\Actions;

use App\Events\StreamFailureReported;
use App\Models\LiveEvent;
use App\Models\StreamIncident;
use Illuminate\Support\Facades\DB;
use App\Enums\EventStatus;


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


            return StreamIncident::create(
                [
                    'live_event_id'              => $event->id,
                    'failed_at'                  => $failedAt,
                    'automatic_refunds_eligible' => $eligible,
                ]
            );
        });
        StreamFailureReported::dispatch(
            $incident
        );

        return $incident;
    }
}
