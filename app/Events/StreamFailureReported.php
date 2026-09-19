<?php

namespace App\Events;

use App\Models\StreamIncident;
use Illuminate\Foundation\Events\Dispatchable;

class StreamFailureReported
{
    use Dispatchable;

    public function __construct(public StreamIncident $incident) {}
}
