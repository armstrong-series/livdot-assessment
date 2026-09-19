<?php

namespace App\Http\Controllers;

use App\Models\LiveEvent;
use App\Services\StreamService;

class StreamController extends Controller
{
    public function __construct(
        private StreamService $streamService
    ) {}

    public function recordFailure(LiveEvent $event)
    {
        abort_unless(
            $event->host_id === request()->user()->id || request()->user()->isAdmin(),
            403
        );

        return livdotResponse(
            $this->streamService->recordBroadcastFailure($event),
            201,
            'Recorded stream incident',
            true,
            app('url')->current(),
            [],
            'stream-incidents'
        );
    }
}
