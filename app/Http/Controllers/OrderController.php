<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Actions\UpdateOrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $validated = $request->validated();
        $address = UserAddress::query()->where('user_id', $request->user()->id)->findOrFail($validated['address_id']);

        // Reuse the completed order when the browser repeats the same checkout request.
        $existingOrder = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('checkout_token', $validated['checkout_token'])
            ->first();
        if ($existingOrder) {
            return redirect()->route('orders.show', $existingOrder)->with('success', 'This order was already placed safely.');
        }

        if (($validated['source'] ?? 'product') === 'cart') {
            $cart = Cart::query()->where(['user_id' => $request->user()->id, 'status' => 'active'])->with('items.product.translations', 'items.product.images')->firstOrFail();
            abort_if($cart->items->isEmpty(), 422, 'Your cart is empty.');
            $lines = $cart->items->map(fn ($item) => ['product' => $item->product, 'quantity' => $item->quantity]);
            $order = $placeOrder->handle(
                $request->user(),
                $address,
                $lines,
                $validated['payment_method'],
                $validated['customer_note'] ?? null,
                $validated['checkout_token'],
                $cart,
            );

            return redirect()->route('orders.show', $order)->with('success', 'Order '.$order->order_number.' was placed successfully.');
        } else {
            $product = Product::query()->active()->with(['translations', 'images'])->findOrFail($validated['product_id']);
            $order = $placeOrder->handle(
                $request->user(),
                $address,
                collect([['product' => $product, 'quantity' => (int) ($validated['quantity'] ?? 1)]]),
                $validated['payment_method'],
                $validated['customer_note'] ?? null,
                $validated['checkout_token'],
            );

            // Keep a direct purchase on the selected product after success.
            return redirect()->route('products.show', $product)->with('success', 'Order '.$order->order_number.' was placed successfully.');
        }
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $order->load(['items.product', 'payments', 'statusHistories']);

        return view('pages.order-show', compact('order'));
    }

    public function cancel(Request $request, Order $order, UpdateOrderStatus $updateOrderStatus): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        if ($order->payment_status === 'paid') {
            return back()->withErrors(['order' => 'Please contact support to cancel a paid order.']);
        }
        $updateOrderStatus->handle($order, 'cancelled', $request->user(), $order->payment_status);

        return back()->with('success', 'Order cancelled and reserved stock restored.');
    }

    public function reorder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $order->load('items.product');
        $addedItems = 0;

        // Add only products that are still active and available to a fresh cart.
        DB::transaction(function () use ($request, $order, &$addedItems): void {
            $cart = Cart::query()->firstOrCreate(
                ['user_id' => $request->user()->id, 'status' => 'active'],
                ['currency' => 'USD'],
            );
            foreach ($order->items as $orderItem) {
                $product = $orderItem->product;
                if (! $product || $product->status !== 'active' || ($product->track_stock && $product->stock_quantity < 1)) {
                    continue;
                }

                $cartItem = CartItem::query()->firstOrNew(['cart_id' => $cart->id, 'product_id' => $product->id]);
                $availableQuantity = $product->track_stock ? $product->stock_quantity : 99;
                $cartItem->quantity = min($availableQuantity, ($cartItem->exists ? $cartItem->quantity : 0) + $orderItem->quantity);
                $cartItem->unit_price = $product->price;
                $cartItem->save();
                $addedItems++;
            }
        });

        if ($addedItems === 0) {
            return back()->withErrors(['order' => 'No products from this order are currently available.']);
        }

        return redirect()->route('cart.index')->with('success', 'Available products were added to your cart.');
    }
}
