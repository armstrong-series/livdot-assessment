<?php

namespace App\Actions;

use App\Models\LiveEvent;
use Illuminate\Support\Facades\DB;
use App\Enums\EventStatus;

class CompleteLiveEventAction
{
    public function execute(LiveEvent $event): LiveEvent
    {
        return DB::transaction(function () use ($event): LiveEvent {
            $event = LiveEvent::lockForUpdate()->findOrFail($event->id);
            abort_unless(
                $event->status === EventStatus::LIVE->value,
                422,
                'Only live events can be completed.'
            );
            $event->update(
                [
                    'status'       => EventStatus::COMPLETED->value,
                    'completed_at' => now()
                ]
            );


            return $event->refresh()->load(
                [
                    'host',
                    'crewAssignments',
                ]
            );
        });
    }
}
