<?php

namespace App\Actions;

use App\Events\PaymentCaptured;
use App\Models\LedgerEntry;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use App\Enums\TicketStatus;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentStatus;

class CapturePaymentAction
{
    public function execute(string $providerEventId, string $reference): PaymentTransaction
    {

        $payment = DB::transaction(function () use ($providerEventId, $reference): PaymentTransaction {
            $payment = PaymentTransaction::where('provider_reference', $reference)
                ->lockForUpdate()->firstOrFail();


            if ($payment->status === PaymentStatus::CAPTURED->value) {
                return $payment;
            }
            abort_if(
                PaymentTransaction::where('provider_event_id', $providerEventId)->exists(),
                409,
                'Webhook already belongs to another payment.'
            );
            $payment->update(
                [
                    'status'            => PaymentStatus::CAPTURED->value,
                    'provider_event_id' => $providerEventId,
                    'captured_at'       => now()
                ]
            );
            $ticket = $payment->ticket()->with('liveEvent')->firstOrFail();
            $ticket->update(
                [
                    'status'     => TicketStatus::ACTIVE->value,
                    'granted_at' => now()
                ]
            );
            LedgerEntry::create(
                [
                    'live_event_id'  => $ticket->live_event_id,
                    'entry_type'     => LedgerEntryType::TICKET_SALE->value,
                    'amount_kobo'    => $payment->amount_kobo,
                    'reference_type' => 'payment_transaction',
                    'reference_id'   => $payment->id
                ]
            );

            return $payment;
        });
        PaymentCaptured::dispatch(
            $payment
        );

        return $payment;
    }
}
