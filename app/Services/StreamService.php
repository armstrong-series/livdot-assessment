<?php

namespace App\Services;

use App\Actions\ReportStreamFailureAction;
use App\Models\LiveEvent;
use App\Models\StreamIncident;

class StreamService
{
    public function __construct(private ReportStreamFailureAction $reportStreamFailure) {}

    public function recordBroadcastFailure(LiveEvent $event): StreamIncident
    {
        return $this->reportStreamFailure->execute($event);
    }
}
