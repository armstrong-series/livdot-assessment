<?php

namespace App\Actions;

use App\Models\CrewAssignment;
use App\Models\LiveEvent;
use Illuminate\Support\Facades\DB;

class AssignCrewAction
{
    public function execute(LiveEvent $event, array $assignment): CrewAssignment
    {
        return DB::transaction(fn(): CrewAssignment => CrewAssignment::create(
            [
                'live_event_id' => $event->id,
                ...$assignment
            ]
        ));
    }
}
