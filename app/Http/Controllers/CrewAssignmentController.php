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
        abort_unless(
            $event->host_id === $request->user()->id,
            403
        );

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
        $assignment = $this->crewAssignmentService->confirmCrewAvailability($assignment);

        if ($assignment === null) {
            return response()->json([
                'message' => 'Crew assignment already accepted.',
                'status' => 'success',
                'included' => [],
                'meta' => [],
                'jsonapi' => [
                    'version' => '1.0',
                ],
            ], 200);
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
