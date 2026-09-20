<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

use App\Events\StreamFailureReported;
use App\Jobs\ProcessIncidentRefundsJob;

class StreamFailureReportedListener implements ShouldQueue
{

    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 60];
    }


    public function handle(StreamFailureReported $event): void
    {
        $incident = $event->incident;
        $incident->loadMissing('liveEvent');

        if ($incident->automatic_refunds_eligible) {
            ProcessIncidentRefundsJob::dispatch(
                $incident->id
            );
        }
    }
}
