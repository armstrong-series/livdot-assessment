<?php

namespace App\Actions;

use App\Models\LiveEvent;
use Illuminate\Support\Facades\DB;
use App\Enums\EventStatus;

class StartLiveEventAction
{
    public function execute(LiveEvent $event): LiveEvent
    {

        return DB::transaction(function () use ($event): LiveEvent {
            $event = LiveEvent::lockForUpdate()->findOrFail($event->id);
            abort_unless(
                $event->status === EventStatus::SCHEDULED->value,
                422,
                'Only scheduled events can go live.'
            );
            abort_unless(
                $event->crewAssignments()->where('status', 'accepted')->exists(),
                422,
                'An accepted crew assignment is required.'
            );
            $event->update(
                [
                    'status'     => EventStatus::LIVE->value,
                    'started_at' => now(),
                ]
            );

            return $event->refresh()->load([
                'host',
                'crewAssignments',
            ]);
        });
    }
}
