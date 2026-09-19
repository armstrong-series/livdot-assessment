<?php

namespace App\Services;

use App\Actions\CompleteLiveEventAction;
use App\Actions\CreateLiveEventAction;
use App\Actions\StartLiveEventAction;
use App\Models\LiveEvent;
use App\Models\User;

class LiveEventService
{
    public function __construct(
        private CreateLiveEventAction $createLiveEvent,
        private StartLiveEventAction $startLiveEvent,
        private CompleteLiveEventAction $completeLiveEvent
    ) {}

    public function hostEvent(User $host, array $attributes): LiveEvent
    {
        return $this->createLiveEvent->execute($host, $attributes);
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
