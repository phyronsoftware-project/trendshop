<?php

namespace App\Actions;

use App\Models\AdminNotification;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Support\CambodiaProvince;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    /** @param Collection<int, array{product: Product, quantity: int}> $lines */
    public function handle(
        User $user,
        UserAddress $address,
        Collection $lines,
        string $paymentMethod,
        ?string $customerNote,
        string $checkoutToken,
        ?Cart $cart = null,
    ): Order {
        // Return the original order when the same checkout form is submitted again.
        $existingOrder = Order::query()
            ->where('user_id', $user->id)
            ->where('checkout_token', $checkoutToken)
            ->first();
        if ($existingOrder) {
            return $existingOrder;
        }

        return Cache::lock("checkout:{$user->id}:{$checkoutToken}", 15)->block(5, function () use ($user, $address, $lines, $paymentMethod, $customerNote, $checkoutToken, $cart): Order {
            return DB::transaction(function () use ($user, $address, $lines, $paymentMethod, $customerNote, $checkoutToken, $cart): Order {
                $existingOrder = Order::query()
                    ->where('user_id', $user->id)
                    ->where('checkout_token', $checkoutToken)
                    ->first();
                if ($existingOrder) {
                    return $existingOrder;
                }

                $subtotal = 0.0;
                foreach ($lines as $line) {
                    $product = Product::query()->lockForUpdate()->findOrFail($line['product']->id);
                    if ($product->status !== 'active') {
                        throw ValidationException::withMessages(['product_id' => 'This product is no longer available.']);
                    }
                    if ($product->track_stock && $line['quantity'] > $product->stock_quantity) {
                        throw ValidationException::withMessages(['quantity' => "Not enough stock for {$line['product']->translation()?->name}."]);
                    }
                    $line['product']->setRawAttributes($product->getAttributes(), true);
                    $subtotal += (float) $product->price * $line['quantity'];
                }

                $deliverySettings = Setting::query()
                    ->whereIn('setting_key', ['delivery_fee', 'free_delivery_minimum', 'province_delivery_fees'])
                    ->pluck('value', 'setting_key');
                $provinceFees = CambodiaProvince::feesFromJson($deliverySettings['province_delivery_fees'] ?? null);
                $deliveryFee = (float) ($provinceFees[$address->city_province] ?? $deliverySettings['delivery_fee'] ?? 2);
                $freeDeliveryMinimum = (float) ($deliverySettings['free_delivery_minimum'] ?? 0);
                if ($freeDeliveryMinimum > 0 && $subtotal >= $freeDeliveryMinimum) {
                    $deliveryFee = 0.0;
                }
                $order = Order::create([
                    'order_number' => 'TS-'.now()->format('YmdHis').'-'.strtoupper(str()->random(5)),
                    'checkout_token' => $checkoutToken,
                    'user_id' => $user->id, 'user_address_id' => $address->id, 'status' => 'pending', 'payment_status' => 'unpaid',
                    'currency' => 'USD', 'subtotal' => $subtotal, 'discount_total' => 0, 'delivery_fee' => $deliveryFee, 'grand_total' => $subtotal + $deliveryFee,
                    'recipient_name' => $address->recipient_name, 'recipient_phone' => $address->phone,
                    'delivery_address_line_1' => $address->address_line_1, 'delivery_address_line_2' => $address->address_line_2,
                    'delivery_commune' => $address->commune, 'delivery_district' => $address->district,
                    'delivery_city_province' => $address->city_province, 'delivery_postal_code' => $address->postal_code,
                    'delivery_country_code' => $address->country_code, 'customer_note' => $customerNote, 'placed_at' => now(),
                ]);

                foreach ($lines as $line) {
                    $product = $line['product'];
                    $quantity = $line['quantity'];
                    $order->items()->create([
                        'product_id' => $product->id, 'product_sku' => $product->sku,
                        'product_name' => $product->translation()?->name ?? $product->slug,
                        'product_image_path' => $product->primaryImage()?->image_path,
                        'unit_price' => $product->price, 'quantity' => $quantity, 'line_total' => (float) $product->price * $quantity,
                    ]);
                    if ($product->track_stock) {
                        $product->decrement('stock_quantity', $quantity);
                    }
                }

                // Keep payment intent and order history synchronized with the new order.
                $order->payments()->create([
                    'provider' => $paymentMethod,
                    'amount' => $order->grand_total,
                    'currency' => $order->currency,
                    'status' => 'pending',
                ]);
                $order->statusHistories()->create([
                    'changed_by' => $user->id,
                    'from_status' => null,
                    'to_status' => 'pending',
                    'note' => 'Order placed by customer.',
                ]);
                $cart?->update(['status' => 'converted']);

                AdminNotification::create(['order_id' => $order->id, 'type' => 'new_order', 'title' => 'New order '.$order->order_number, 'message' => $user->name.' placed a new order.', 'data' => ['user_id' => $user->id, 'grand_total' => $order->grand_total]]);

                return $order;
            });
        });
    }
}
