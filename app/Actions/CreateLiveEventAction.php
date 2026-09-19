<?php

namespace App\Actions;

use App\Models\LiveEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Enums\EventStatus;

class CreateLiveEventAction
{

    public function execute(User $host, array $attributes): LiveEvent
    {
        return DB::transaction(fn(): LiveEvent => LiveEvent::create(
            [
                ...$attributes,
                'host_id' => $host->id,
                'status'  => EventStatus::SCHEDULED->value,
            ]
        ));
    }
}
