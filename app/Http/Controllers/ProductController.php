<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\WishlistItem;
use App\Support\CambodiaProvince;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()->active()->with(['translations', 'images', 'category.translations'])->latest('published_at')->get();
        $categories = Category::query()->where('is_active', true)->with('translations')->orderBy('sort_order')->get();
        // Load one compact set so every product card can render its saved state without N+1 queries.
        $wishlistedProductIds = $request->user()
            ? WishlistItem::query()->where('user_id', $request->user()->id)->pluck('product_id')
            : collect();
        // Load the active cart state once so card buttons do not perform per-product queries.
        $cartProductIds = $request->user()
            ? CartItem::query()->whereHas('cart', fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->where('status', 'active'))->pluck('product_id')
            : collect();
        // Rank the ten highest-selling active products for the storefront ticker and feature.
        $topSellers = $products->sortByDesc('sold_quantity')->take(10)->values();
        $bestSeller = $topSellers->first();

        // Present real store-wide social proof without exposing any customer's private account data.
        $storeSummary = [
            'customers_count' => User::query()->where('role', 'customer')->where('status', 'active')->count(),
            'delivered_orders_count' => Order::query()->where('status', 'delivered')->count(),
            'items_sold' => (int) OrderItem::query()->whereHas('order', fn ($query) => $query->where('status', 'delivered'))->sum('quantity'),
            'products_count' => $products->count(),
        ];

        return view('products.index', compact('bestSeller', 'cartProductIds', 'categories', 'products', 'storeSummary', 'topSellers', 'wishlistedProductIds'));
    }

    public function show(Request $request, Product $product): View
    {
        abort_unless($product->status === 'active', 404);
        $product->load(['translations', 'images', 'category.translations']);

        // Keep the related catalogue within the current product category.
        $relatedProducts = Product::query()->active()->whereKeyNot($product->id)
            ->where('category_id', $product->category_id)
            ->with(['translations', 'images', 'category.translations'])
            ->orderByDesc('sold_quantity')
            ->limit(15)
            ->get();
        $deliveryFees = CambodiaProvince::feesFromJson(
            Setting::query()->where('setting_key', 'province_delivery_fees')->value('value'),
        );
        /** @var Collection<int, UserAddress> $addresses */
        $addresses = auth()->check()
            ? auth()->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get()
            : collect();
        // Keep related product cards synchronized with the signed-in customer's cart.
        $cartProductIds = $request->user()
            ? CartItem::query()->whereHas('cart', fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->where('status', 'active'))->pluck('product_id')
            : collect();

        return view('products.show', compact('addresses', 'cartProductIds', 'deliveryFees', 'product', 'relatedProducts'));
    }
}
