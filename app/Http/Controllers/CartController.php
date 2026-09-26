<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Setting;
use App\Support\CambodiaProvince;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = Cart::query()->firstOrCreate(['user_id' => $request->user()->id, 'status' => 'active'], ['currency' => 'USD']);
        $cart->load('items.product.translations', 'items.product.images');
        $deliveryFees = CambodiaProvince::feesFromJson(
            Setting::query()->where('setting_key', 'province_delivery_fees')->value('value'),
        );

        return view('pages.cart', compact('cart', 'deliveryFees'));
    }

    public function store(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->status === 'active', 404);
        $validated = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:99']]);
        $quantity = (int) ($validated['quantity'] ?? 1);
        $cart = Cart::query()->where(['user_id' => $request->user()->id, 'status' => 'active'])->first();
        $item = $cart
            ? CartItem::query()->firstOrNew(['cart_id' => $cart->id, 'product_id' => $product->id])
            : null;

        // Toggle existing card items only for no-refresh JSON requests.
        if ($request->expectsJson() && $item?->exists) {
            $item->delete();

            return response()->json([
                'in_cart' => false,
                'message' => 'Product removed from cart.',
            ]);
        }

        if ($quantity > $product->stock_quantity) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Requested quantity is not available.'], 422);
            }

            return back()->withErrors(['quantity' => 'Requested quantity is not available.']);
        }

        // Create cart records only after the requested quantity passes validation.
        $cart ??= Cart::query()->create(['user_id' => $request->user()->id, 'status' => 'active', 'currency' => 'USD']);
        $item ??= CartItem::query()->firstOrNew(['cart_id' => $cart->id, 'product_id' => $product->id]);
        $item->quantity = min($product->stock_quantity, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->unit_price = $product->price;
        $item->save();

        // Return the persisted state to AJAX cards without changing regular form redirects.
        if ($request->expectsJson()) {
            return response()->json([
                'in_cart' => true,
                'message' => 'Product added to cart.',
            ]);
        }

        return back()->with('success', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->cart()->where('user_id', $request->user()->id)->exists(), 403);
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:'.$item->product->stock_quantity]]);
        $item->update($validated);

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->cart()->where('user_id', $request->user()->id)->exists(), 403);
        $item->delete();

        return back()->with('success', 'Product removed from cart.');
    }
}
