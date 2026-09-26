<?php

namespace App\Actions;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Support\CambodiaProvince;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    /** @param Collection<int, array{product: Product, quantity: int}> $lines */
    public function handle(User $user, UserAddress $address, Collection $lines): Order
    {
        return DB::transaction(function () use ($user, $address, $lines): Order {
            $subtotal = 0.0;
            foreach ($lines as $line) {
                $product = Product::query()->lockForUpdate()->findOrFail($line['product']->id);
                if ($line['quantity'] > $product->stock_quantity) {
                    throw ValidationException::withMessages(['quantity' => "Not enough stock for {$product->translation()?->name}."]);
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
                'user_id' => $user->id, 'user_address_id' => $address->id, 'status' => 'pending', 'payment_status' => 'unpaid',
                'currency' => 'USD', 'subtotal' => $subtotal, 'discount_total' => 0, 'delivery_fee' => $deliveryFee, 'grand_total' => $subtotal + $deliveryFee,
                'recipient_name' => $address->recipient_name, 'recipient_phone' => $address->phone,
                'delivery_address_line_1' => $address->address_line_1, 'delivery_address_line_2' => $address->address_line_2,
                'delivery_commune' => $address->commune, 'delivery_district' => $address->district,
                'delivery_city_province' => $address->city_province, 'delivery_postal_code' => $address->postal_code,
                'delivery_country_code' => $address->country_code, 'placed_at' => now(),
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
                $product->decrement('stock_quantity', $quantity);
                $product->increment('sold_quantity', $quantity);
            }

            AdminNotification::create(['order_id' => $order->id, 'type' => 'new_order', 'title' => 'New order '.$order->order_number, 'message' => $user->name.' placed a new order.', 'data' => ['user_id' => $user->id, 'grand_total' => $order->grand_total]]);

            return $order;
        });
    }
}
