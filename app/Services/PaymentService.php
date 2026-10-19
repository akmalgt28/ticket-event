<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\EticketMail;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private TicketIssuanceService $ticketIssuance) {}

    public function selectManualTransfer(Order $order): Payment
    {
        return DB::transaction(function () use ($order): Payment {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->order_status !== OrderStatus::Pending || $lockedOrder->expires_at?->isPast()) {
                throw ValidationException::withMessages([
                    'order' => 'This order is no longer accepting payment.',
                ]);
            }

            $pendingPayment = $lockedOrder->payments()
                ->where('status', PaymentStatus::Pending)
                ->latest('id')
                ->first();

            if ($pendingPayment) {
                return $pendingPayment;
            }

            $lockedOrder->update(['payment_method' => 'bank_transfer']);

            return $lockedOrder->payments()->create([
                'provider' => 'manual',
                'method' => 'bank_transfer',
                'amount' => $lockedOrder->total_amount,
                'status' => PaymentStatus::Pending,
            ]);
        });
    }

    /**
     * @param  array{status: string, provider_transaction_id?: ?string, note?: ?string}  $data
     */
    public function verifyManual(Order $order, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($order, $data, $user): Payment {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = $lockedOrder->payments()->latest('id')->lockForUpdate()->first();

            if (! $payment) {
                throw ValidationException::withMessages([
                    'order_id' => 'No payment exists for this order.',
                ]);
            }

            $targetStatus = PaymentStatus::from($data['status']);

            if ($payment->status !== PaymentStatus::Pending) {
                if ($payment->status === $targetStatus) {
                    return $payment;
                }

                throw ValidationException::withMessages([
                    'status' => 'This payment has already been processed.',
                ]);
            }

            $reference = $data['provider_transaction_id'] ?? null;

            if ($reference && Payment::query()
                ->where('provider_transaction_id', $reference)
                ->where($payment->getKeyName(), '!=', $payment->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'provider_transaction_id' => 'This transaction reference has already been used.',
                ]);
            }

            $oldValues = [
                'payment_status' => $payment->status->value,
                'order_status' => $lockedOrder->order_status->value,
            ];

            $payment->update([
                'status' => $targetStatus,
                'provider_transaction_id' => $reference,
                'paid_at' => $targetStatus === PaymentStatus::Paid ? now() : null,
            ]);

            $lockedOrder->update([
                'payment_status' => $targetStatus,
                'order_status' => $targetStatus === PaymentStatus::Paid
                    ? OrderStatus::Paid
                    : OrderStatus::Pending,
                'paid_at' => $targetStatus === PaymentStatus::Paid ? now() : null,
            ]);

            if ($targetStatus === PaymentStatus::Paid) {
                $issued = $this->ticketIssuance->issue($lockedOrder);
                $lockedOrder->load('event');
                $issued['tickets']->each(fn ($ticket) => $ticket->load('attendee'));

                Mail::to($lockedOrder->customer_email)->queue(
                    (new EticketMail(
                        $lockedOrder->order_number,
                        $lockedOrder->event->title,
                        $issued['tickets'],
                        $issued['qr_tokens'],
                    ))->afterCommit(),
                );
            }

            $auditLog = new AuditLog([
                'action' => 'payment.manual_verified',
                'old_values' => $oldValues,
                'new_values' => [
                    'payment_status' => $targetStatus->value,
                    'order_status' => $lockedOrder->order_status->value,
                    'note' => $data['note'] ?? null,
                ],
            ]);
            $auditLog->user()->associate($user);
            $auditLog->auditable()->associate($payment);
            $auditLog->save();

            return $payment->fresh();
        });
    }
}
