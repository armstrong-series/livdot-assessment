<?php

namespace App\Models;

use Database\Factories\LiveEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveEvent extends Model
{
    /** @use HasFactory<LiveEventFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'host_id',
        'title',
        'ticket_price_kobo',
        'scheduled_duration_minutes',
        'scheduled_starts_at',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_starts_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function crewAssignments(): HasMany
    {
        return $this->hasMany(CrewAssignment::class);
    }

    public function streamIncidents(): HasMany
    {
        return $this->hasMany(StreamIncident::class);
    }
}