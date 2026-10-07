<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatus
{
    /** @var array<string, array<int, string>> */
    private const ALLOWED_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => ['refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];

    public function handle(
        Order $order,
        string $nextStatus,
        User $actor,
        ?string $paymentStatus = null,
        ?string $adminNote = null,
        ?string $shippingCarrier = null,
        ?string $trackingNumber = null,
    ): Order {
        return DB::transaction(function () use ($order, $nextStatus, $actor, $paymentStatus, $adminNote, $shippingCarrier, $trackingNumber): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $previousStatus = $lockedOrder->status;

            // Reject status jumps that bypass fulfillment or reopen completed orders.
            if ($nextStatus !== $previousStatus && ! in_array($nextStatus, self::ALLOWED_TRANSITIONS[$previousStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Order cannot move from {$previousStatus} to {$nextStatus}.",
                ]);
            }

            $lockedOrder->load('items.product');
            $updates = [
                'status' => $nextStatus,
                'handled_by' => $actor->id,
                'admin_note' => $adminNote,
                'shipping_carrier' => $shippingCarrier,
                'tracking_number' => $trackingNumber,
            ];
            if ($paymentStatus !== null) {
                $updates['payment_status'] = $paymentStatus;
            }

            if ($nextStatus !== $previousStatus && in_array($nextStatus, ['confirmed', 'shipped', 'delivered', 'cancelled'], true)) {
                $updates[$nextStatus.'_at'] = now();
            }

            // Count sales only after delivery and restore inventory once on cancellation or refund.
            if ($nextStatus === 'delivered' && $lockedOrder->sold_counted_at === null) {
                foreach ($lockedOrder->items as $item) {
                    $item->product?->increment('sold_quantity', $item->quantity);
                }
                $updates['sold_counted_at'] = now();
            }

            if (in_array($nextStatus, ['cancelled', 'refunded'], true) && $lockedOrder->stock_released_at === null) {
                foreach ($lockedOrder->items as $item) {
                    if ($item->product?->track_stock) {
                        $item->product->increment('stock_quantity', $item->quantity);
                    }
                    if ($lockedOrder->sold_counted_at !== null && $item->product) {
                        $item->product->update([
                            'sold_quantity' => max(0, $item->product->sold_quantity - $item->quantity),
                        ]);
                    }
                }
                $updates['stock_released_at'] = now();
                if ($lockedOrder->sold_counted_at !== null) {
                    $updates['sold_counted_at'] = null;
                }
            }

            $lockedOrder->update($updates);

            if ($paymentStatus !== null) {
                $payment = $lockedOrder->payments()->latest('id')->first();
                if ($payment) {
                    $payment->update([
                        'status' => match ($paymentStatus) {
                            'unpaid', 'pending' => 'pending',
                            default => $paymentStatus,
                        },
                        'paid_at' => $paymentStatus === 'paid' ? ($payment->paid_at ?? now()) : $payment->paid_at,
                    ]);
                }
            }

            if ($nextStatus !== $previousStatus) {
                $lockedOrder->statusHistories()->create([
                    'changed_by' => $actor->id,
                    'from_status' => $previousStatus,
                    'to_status' => $nextStatus,
                    'note' => $actor->role === 'admin' ? 'Updated from admin dashboard.' : 'Cancelled by customer.',
                ]);
            }

            return $lockedOrder->refresh();
        });
    }

    /** @return array<int, string> */
    public function availableStatuses(Order $order): array
    {
        return [$order->status, ...(self::ALLOWED_TRANSITIONS[$order->status] ?? [])];
    }
}
