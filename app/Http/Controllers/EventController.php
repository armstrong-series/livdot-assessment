<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Models\LiveEvent;
use App\Services\LiveEventService;

class EventController extends Controller
{
    public function __construct(
        private LiveEventService $liveEventService
    ) {}

    public function hostEvent(StoreEventRequest $request)
    {
        authorizedRole('host');

        return livdotResponse(
            $this->liveEventService->hostEvent(
                $request->user(),
                $request->validated()
            ),
            201,
            'Plan recorded!',
            true,
            app('url')->current(),
            [],
            'events'
        );
    }

    public function beginBroadcast(LiveEvent $event)
    {

        authorizedRole('host');

        abort_unless(
            $event->host_id === request()->user()->id,
            403,
            'Only the event host can start this event.'
        );



        return livdotResponse(
            $this->liveEventService->beginBroadcast($event),
            200,
            'events',
            true,
            app('url')->current(),
            [],
            'events'
        );
    }

    public function finalizeBroadcast(LiveEvent $event)
    {

        authorizedRole('host');

        abort_unless($event->host_id === request()->user()->id, 403);

        return livdotResponse(
            $this->liveEventService->finalizeBroadcast($event),
            200,
            'broadcast finalized!',
            true,
            app('url')->current(),
            [],
            'events'
        );
    }
}
