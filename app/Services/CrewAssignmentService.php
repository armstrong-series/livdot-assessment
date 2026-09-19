<?php

namespace App\Services;

use App\Actions\AcceptCrewAssignmentAction;
use App\Actions\AssignCrewAction;
use App\Models\CrewAssignment;
use App\Models\LiveEvent;

class CrewAssignmentService
{
    public function __construct(
        private AssignCrewAction $assignCrew,
        private AcceptCrewAssignmentAction $acceptCrewAssignment
    ) {}

    public function assignProductionCrew(LiveEvent $event, array $assignment): CrewAssignment
    {
        return $this->assignCrew->execute($event, $assignment);
    }


    public function confirmCrewAvailability(CrewAssignment $assignment): ?CrewAssignment
    {
        return $this->acceptCrewAssignment->execute($assignment);
    }
}
