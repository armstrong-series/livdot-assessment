<?php

namespace App\Actions;

use App\Models\CrewAssignment;
use Illuminate\Support\Facades\DB;

class AcceptCrewAssignmentAction
{

    public function execute(CrewAssignment $assignment): ?CrewAssignment
    {
        return DB::transaction(function () use ($assignment): ?CrewAssignment {
            $assignment = CrewAssignment::whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($assignment->status === 'accepted') {
                return null;
            }

            $assignment->update(
                [
                    'status' => 'accepted',
                ]
            );


            return $assignment->refresh()->load(
                [
                    'liveEvent',
                    'crewMember',
                ]
            );
        });
    }
}
