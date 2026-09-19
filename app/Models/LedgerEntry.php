<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'live_event_id',
        'entry_type',
        'amount_kobo',
        'reference_type',
        'reference_id',
    ];
}
