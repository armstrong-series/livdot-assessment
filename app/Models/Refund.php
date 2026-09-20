<?php

namespace App\Models;

use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'ticket_id',
        'stream_incident_id',
        'amount_kobo',
        'status',
        'reason'
    ];
}
