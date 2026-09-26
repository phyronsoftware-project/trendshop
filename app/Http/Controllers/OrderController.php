<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Models\Cart;
use App\Models\Product;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer'], 'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'], 'source' => ['nullable', 'in:cart,product'],
        ]);
        $address = UserAddress::query()->where('user_id', $request->user()->id)->findOrFail($validated['address_id']);
        $redirectUrl = route('profile').'#orders';

        if (($validated['source'] ?? 'product') === 'cart') {
            $cart = Cart::query()->where(['user_id' => $request->user()->id, 'status' => 'active'])->with('items.product.translations', 'items.product.images')->firstOrFail();
            abort_if($cart->items->isEmpty(), 422, 'Your cart is empty.');
            $lines = $cart->items->map(fn ($item) => ['product' => $item->product, 'quantity' => $item->quantity]);
            $order = $placeOrder->handle($request->user(), $address, $lines);
            $cart->update(['status' => 'converted']);
        } else {
            $product = Product::query()->with(['translations', 'images'])->findOrFail($validated['product_id']);
            $order = $placeOrder->handle($request->user(), $address, collect([['product' => $product, 'quantity' => (int) ($validated['quantity'] ?? 1)]]));

            // Keep a direct purchase on the selected product after success.
            $redirectUrl = route('products.show', $product);
        }

        return redirect($redirectUrl)->with('success', 'Order '.$order->order_number.' was placed successfully.');
    }
}
