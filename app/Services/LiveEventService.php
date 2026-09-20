<?php

namespace App\Services;

use App\Actions\CompleteLiveEventAction;
use App\Actions\CreateLiveEventAction as HostLiveEventAction;
use App\Actions\StartLiveEventAction;
use App\Models\LiveEvent;
use App\Models\User;

class LiveEventService
{
    public function __construct(
        private HostLiveEventAction $hostLiveEventAction,
        private StartLiveEventAction $startLiveEvent,
        private CompleteLiveEventAction $completeLiveEvent
    ) {}

    public function hostEvent(User $host, array $attributes): LiveEvent
    {
        return $this->hostLiveEventAction->execute($host, $attributes);
    }

    public function beginBroadcast(LiveEvent $event): LiveEvent
    {
        return $this->startLiveEvent->execute($event);
    }

    public function finalizeBroadcast(LiveEvent $event): LiveEvent
    {
        return $this->completeLiveEvent->execute($event);
    }
}
