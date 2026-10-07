<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $items = WishlistItem::query()->where('user_id', $request->user()->id)->with(['product.translations', 'product.images', 'product.category.translations'])->latest()->get();
        // Match each wishlist card with the customer's persisted active cart state.
        $cartProductIds = CartItem::query()->whereHas('cart', fn ($query) => $query
            ->where('user_id', $request->user()->id)
            ->where('status', 'active'))->pluck('product_id');

        return view('pages.wishlist', compact('cartProductIds', 'items'));
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $item = WishlistItem::query()->where(['user_id' => $request->user()->id, 'product_id' => $product->id])->first();
        if ($item) {
            $item->delete();
            $message = 'Product removed from wishlist.';
            $wishlisted = false;
        } else {
            WishlistItem::create(['user_id' => $request->user()->id, 'product_id' => $product->id]);
            $message = 'Product saved to wishlist.';
            $wishlisted = true;
        }

        // Return state for no-refresh storefront actions while preserving regular form redirects.
        if ($request->expectsJson()) {
            return response()->json([
                'wishlisted' => $wishlisted,
                'wishlist_count' => WishlistItem::query()->where('user_id', $request->user()->id)->count(),
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
