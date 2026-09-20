<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCrewAssignmentRequest;
use App\Models\CrewAssignment;
use App\Models\LiveEvent;
use App\Services\CrewAssignmentService;
use App\Http\Requests\ConfirmCrewAvailabilityRequest;

class CrewAssignmentController extends Controller
{
    public function __construct(
        private CrewAssignmentService $crewAssignmentService
    ) {}

    public function assignCrew(StoreCrewAssignmentRequest $request, LiveEvent $event)
    {

        authorizedRole('host');

        return livdotResponse(
            $this->crewAssignmentService->assignProductionCrew(
                $event,
                $request->validated()
            ),
            201,
            'crew-assignments'
        );
    }

    public function confirmAvailability(ConfirmCrewAvailabilityRequest $request, CrewAssignment $assignment)
    {
        authorizedRole('host');

        $assignment = $this->crewAssignmentService->confirmCrewAvailability($assignment);

        if ($assignment === null) {
            return livdotResponse(
                null,
                200,
                'Crew assignment already accepted..',
                false,
                app('url')->current(),
                [],
                'crew-assignments'
            );
        }

        return livdotResponse(
            $assignment,
            200,
            'Crew assignment accepted.',
            true,
            app('url')->current(),
            [],
            'crew-assignments'
        );
    }
}
