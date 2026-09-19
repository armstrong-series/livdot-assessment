<?php

namespace App\Models;

use Database\Factories\CrewAssignmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewAssignment extends Model
{
    /** @use HasFactory<CrewAssignmentFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'live_event_id',
        'crew_member_id',
        'role',
        'status'
    ];

    public function liveEvent(): BelongsTo
    {
        return $this->belongsTo(LiveEvent::class);
    }

    public function crewMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'crew_member_id');
    }
}
