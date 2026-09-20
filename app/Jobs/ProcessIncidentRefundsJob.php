<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\StreamIncident;
use App\Actions\IssueIncidentRefundsAction;

class ProcessIncidentRefundsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(
        public string $incidentId
    ) {}

    public function handle(
        IssueIncidentRefundsAction $issueIncidentRefunds
    ): void {
        $issueIncidentRefunds->execute(
            StreamIncident::findOrFail($this->incidentId)
        );
    }
}
