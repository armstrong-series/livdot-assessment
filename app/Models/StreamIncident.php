<?php

namespace App\Models;

use Database\Factories\StreamIncidentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamIncident extends Model
{
    /** @use HasFactory<StreamIncidentFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'live_event_id',
        'failed_at',
        'automatic_refunds_eligible',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
            'automatic_refunds_eligible' => 'boolean'
        ];
    }

    public function liveEvent(): BelongsTo
    {
        return $this->belongsTo(LiveEvent::class);
    }
}
