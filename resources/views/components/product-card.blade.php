@props(['product', 'index' => 0, 'wishlistMode' => false, 'wishlisted' => false, 'inCart' => false])

@php
    $productText = $product->translation();
    $categoryText = $product->category?->translation();
    $saveAmount = max((float) ($product->compare_at_price ?? 0) - (float) $product->price, 0);
    $image = $product->primaryImage()?->url();
    $available = ! $product->track_stock || $product->stock_quantity > 0;
@endphp

{{-- Present live product data while preserving the approved card design. --}}
<article data-product-card data-category="{{ $product->category?->slug }}" data-search="{{ strtolower(($productText?->name ?? '').' '.($categoryText?->name ?? '')) }}" class="group relative z-[1] flex min-h-[340px] flex-col overflow-visible border border-slate-200 bg-white shadow-[0_8px_20px_rgba(15,23,42,0.05)] transition-all duration-300 hover:z-20 hover:-translate-y-2 hover:border-slate-300 hover:shadow-[0_18px_36px_rgba(15,23,42,0.10)] dark:border-slate-800 dark:bg-[#0e1113] dark:hover:border-slate-700 md:min-h-[336px]">
    <a href="{{ route('products.show', $product) }}" class="relative flex h-32 items-center justify-center overflow-hidden bg-[radial-gradient(circle_at_top,rgba(8,164,255,0.08),transparent_52%)] px-3 pb-2 pt-4 dark:bg-slate-900 sm:h-40">
        <img src="{{ $image }}" alt="{{ $productText?->name }}" class="size-full object-contain" loading="{{ $index < 5 ? 'eager' : 'lazy' }}">
        <span class="product-card-burst absolute right-2 top-2 grid size-12 place-items-center bg-[#173f88] text-[9px] font-extrabold uppercase text-[#173f88]"><span class="relative z-[2]" data-i18n="products.new">New</span></span>
    </a>

    <div class="absolute inset-x-0 bottom-0 z-[5] flex min-h-[170px] flex-col bg-white/95 px-3 pb-2 pt-2.5 transition-all duration-300 group-hover:bottom-[-34px] group-hover:-translate-y-[34px] dark:bg-[#0e1113]/95">
        <p class="min-h-3 text-center text-[10px] font-bold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ $categoryText?->name }}</p>
        <a href="{{ route('products.show', $product) }}" class="mt-1 line-clamp-2 min-h-8 text-center text-sm font-extrabold leading-4 text-slate-700 transition-colors hover:text-[#173f88] dark:text-white dark:hover:text-blue-300">{{ $productText?->name }}</a>
        <div class="mt-1 flex min-h-5 items-center justify-between gap-2 text-[11px] font-semibold">
            <span @class(['rounded-full px-2 py-1 text-[10px] font-bold', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' => $available, 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => ! $available])>{{ $available ? 'In stock' : 'Out of stock' }}</span>
            <span class="text-slate-500 dark:text-slate-400"><span data-i18n="products.qty">Qty</span>: {{ $product->stock_quantity }}</span>
        </div>
        <div class="mt-1 flex items-start justify-between gap-3">
            <p class="text-base font-extrabold text-rose-500">${{ $product->price }}</p>
            <div class="grid justify-items-end gap-1"><span class="border border-[#214f99] bg-white px-2 py-1 text-[10px] font-bold text-[#214f99] dark:bg-[#0e1113] dark:text-blue-300">${{ number_format($saveAmount, 2) }} <span data-i18n="products.discount">OFF</span></span><span class="text-xs font-bold text-slate-600 line-through dark:text-slate-400">${{ $product->compare_at_price }}</span></div>
        </div>
        <div class="mt-1 flex items-start justify-between gap-3">
            <div class="grid flex-1 gap-0.5 text-[11px] leading-4 text-slate-600 dark:text-slate-400"><span><span data-i18n="products.delivery">Delivery</span>: <strong class="font-normal text-[#214f99]">$2.00</strong></span><span class="truncate text-slate-400" data-i18n="products.deliveryValue">$2.00 in Phnom Penh</span></div>
            {{-- Use the same one-click AJAX favorite toggle on product and wishlist cards. --}}
            <form method="POST" action="{{ route('wishlist.toggle', $product) }}" data-wishlist-ajax data-login-url="{{ route('login') }}" @if($wishlistMode) data-wishlist-remove-card @endif>@csrf<button type="submit" data-wishlist-toggle aria-pressed="{{ $wishlisted ? 'true' : 'false' }}" class="group grid size-[38px] shrink-0 place-items-center border border-slate-200 bg-white text-slate-600 transition-all duration-300 hover:border-[#173f88] hover:text-[#173f88] active:scale-90 aria-pressed:border-rose-400 aria-pressed:bg-rose-50 aria-pressed:text-rose-500 dark:border-slate-700 dark:bg-[#0e1113] dark:text-slate-200 dark:aria-pressed:border-rose-500 dark:aria-pressed:bg-rose-950/30 dark:aria-pressed:text-rose-400" aria-label="{{ $wishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}"><svg class="size-5 group-aria-pressed:fill-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg></button></form>
        </div>
        <div class="max-h-10 overflow-hidden pt-1 transition-all duration-300 md:max-h-0 md:opacity-0 md:group-hover:max-h-10 md:group-hover:opacity-100">
            {{-- Toggle the persisted cart state and fill the button when this product is selected. --}}
            <form method="POST" action="{{ route('cart.store', $product) }}" data-cart-ajax data-login-url="{{ route('login') }}">@csrf<input type="hidden" name="quantity" value="1"><button type="submit" data-cart-toggle aria-pressed="{{ $inCart ? 'true' : 'false' }}" @disabled(! $available && ! $inCart) class="min-h-8 w-full border border-[#173f88] bg-white px-3 text-xs font-bold text-[#173f88] transition-all duration-300 hover:bg-blue-50 active:scale-[.98] disabled:cursor-not-allowed disabled:border-slate-300 disabled:text-slate-400 disabled:opacity-70 aria-pressed:bg-[#173f88] aria-pressed:text-white aria-pressed:hover:bg-[#0a2f6b] dark:bg-[#0e1113] dark:text-blue-300 dark:aria-pressed:bg-[#173f88] dark:aria-pressed:text-white" @if($available || $inCart) data-i18n="{{ $inCart ? 'actions.removeCart' : 'actions.addCart' }}" @endif>{{ $inCart ? 'Remove from cart' : ($available ? 'Add to cart' : 'Out of stock') }}</button></form>
        </div>
    </div>
</article>
