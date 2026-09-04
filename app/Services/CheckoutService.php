<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * @param array{
     *     amount: int|float|string,
     *     currency: string,
     *     provider: string,
     *     event_id: string,
     *     payment_session_id: string,
     *     payment_id: string
     * } $payload
     */
    public function confirm(CustomerOrder $order, array $payload): Payment
    {
        $amount = filter_var($payload['amount'], FILTER_VALIDATE_FLOAT);
        $amountCents = $amount === false ? null : (int) round($amount * 100);
        $orderCents = (int) round((float) $order->total_amount * 100);

        if ($amountCents === null || $amountCents !== $orderCents || $payload['currency'] !== $order->currency) {
            throw ValidationException::withMessages(['amount' => __('crm.full_payment_only')]);
        }

        return DB::transaction(function () use ($order, $payload): Payment {
            $receipt = DB::table('webhook_receipts')->where('provider', $payload['provider'])->where('external_event_id', $payload['event_id'])->first();
            if ($receipt) {
                return Payment::where('external_payment_id', $payload['payment_id'])->firstOrFail();
            }

            DB::table('webhook_receipts')->insert([
                'id' => (string) Str::uuid(), 'provider' => $payload['provider'], 'external_event_id' => $payload['event_id'],
                'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)), 'status' => 'processed',
                'processed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $payment = Payment::create([
                'order_id' => $order->id, 'payment_session_id' => $payload['payment_session_id'],
                'external_payment_id' => $payload['payment_id'], 'amount' => $payload['amount'],
                'currency' => $payload['currency'], 'method' => 'external_card', 'status' => 'paid', 'paid_at' => now(),
            ]);
            $order->update(['status' => 'paid']);

            return $payment;
        });
    }
}
