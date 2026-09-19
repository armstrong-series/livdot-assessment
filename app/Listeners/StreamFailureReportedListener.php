<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

use App\Events\StreamFailureReported;

class StreamFailureReportedListener implements ShouldQueue
{

    /**
     * Handle the event.
     */
    public function handle(StreamFailureReported $event): void
    {
        $incident = $event->incident;
        $incident->loadMissing('liveEvent');
        // Handle post-incident side effects here.
        // Examples: // - Notify the host that the stream failed.
        // - Notify affected viewers that their access was revoked.
        // - Dispatch refund processing jobs.
        // - Notify the operations/admin team. 
        // - Send monitoring/analytics events. if ($incident->automatic_refunds_eligible) { // Refund processing should be handled asynchronously. // // foreach ($incident->liveEvent->tickets as $ticket) { // ProcessRefundJob::dispatch($ticket); // } }
    }
}
