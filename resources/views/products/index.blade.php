@extends('layouts.storefront')

@section('title', 'TrendShop — Products')

@section('content')
    {{-- Present store confidence details without a boxed or tinted section background. --}}
    <section class="border-b border-slate-200 bg-white py-10 dark:border-slate-800 dark:bg-black sm:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div data-store-summary class="text-slate-950 dark:text-white">
                <div class="grid items-start gap-10 lg:grid-cols-[1.05fr_.95fr] lg:gap-16">
                    <div class="max-w-2xl">
                        <div class="flex items-center gap-2">
                            <span class="h-0.5 w-7 bg-blue-600"></span>
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.24em] text-blue-600 dark:text-blue-400" data-i18n="storeProof.eyebrow">WHY SHOP WITH US</p>
                        </div>
                        <h2 class="mt-4 max-w-xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl" data-i18n="storeProof.title">Trusted shopping, made simple</h2>
                        <p class="mt-4 max-w-xl text-sm leading-7 text-slate-600 dark:text-slate-300 sm:text-base" data-i18n="storeProof.description">Clear prices, carefully selected products and dependable local delivery—all in one easy shopping experience.</p>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <a href="#product-catalogue" class="group inline-flex h-11 items-center justify-center gap-2 bg-[#173f88] px-6 text-xs font-bold text-white transition-colors hover:bg-[#0a2f6b]">
                                <span data-i18n="storeProof.shop">Shop collection</span>
                                <svg class="size-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </a>
                            <a href="{{ route('about') }}" class="inline-flex h-11 items-center justify-center border border-slate-300 px-6 text-xs font-bold text-slate-800 transition-colors hover:border-[#173f88] hover:text-[#173f88] dark:border-slate-700 dark:text-white dark:hover:border-blue-400 dark:hover:text-blue-300" data-i18n="storeProof.about">About TrendShop</a>
                        </div>
                    </div>

                    <div data-store-benefits>
                        <div class="flex items-end justify-between gap-4 border-b border-slate-200 pb-4 dark:border-slate-800">
                            <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">TrendShop promise</p><p class="mt-1 text-base font-bold text-slate-950 dark:text-white">Confidence with every order</p></div>
                            <svg class="size-7 text-[#173f88] dark:text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/><path d="m9 14 2 2 4-4"/></svg>
                        </div>
                        <div class="divide-y divide-slate-200 dark:divide-slate-800">
                            <div class="flex items-center gap-4 py-4"><span class="grid size-10 shrink-0 place-items-center bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span><div><strong class="block text-sm text-slate-900 dark:text-white">Clear, honest pricing</strong><span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">No surprise product costs</span></div></div>
                            <div class="flex items-center gap-4 py-4"><span class="grid size-10 shrink-0 place-items-center bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg></span><div><strong class="block text-sm text-slate-900 dark:text-white">Dependable local delivery</strong><span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Simple fees across Cambodia</span></div></div>
                            <div class="flex items-center gap-4 py-4"><span class="grid size-10 shrink-0 place-items-center bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 8 4-8 4-8-4 8-4Z"/><path d="m4 12 8 4 8-4"/></svg></span><div><strong class="block text-sm text-slate-900 dark:text-white">Carefully selected products</strong><span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Useful choices, clearly presented</span></div></div>
                        </div>
                    </div>
                </div>

                {{-- Separate live store totals with simple dividers instead of a background panel. --}}
                <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-6 border-y border-slate-200 py-6 dark:border-slate-800 lg:mt-10 lg:grid-cols-4 lg:divide-x lg:divide-slate-200 dark:lg:divide-slate-800">
                    <div class="flex items-center gap-3 lg:px-6 lg:first:pl-0">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                        <div><strong class="block text-xl font-extrabold sm:text-2xl">{{ number_format($storeSummary['customers_count']) }}</strong><span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 sm:text-xs" data-i18n="storeProof.customers">Happy customers</span></div>
                    </div>
                    <div class="flex items-center gap-3 lg:px-6">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h11v10H4zM15 10h3l2 3v4h-5z"/><circle cx="8" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg></span>
                        <div><strong class="block text-xl font-extrabold sm:text-2xl">{{ number_format($storeSummary['delivered_orders_count']) }}</strong><span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 sm:text-xs" data-i18n="storeProof.orders">Orders delivered</span></div>
                    </div>
                    <div class="flex items-center gap-3 lg:px-6">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h2l2.2 9.5h9.9L20 9H6"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg></span>
                        <div><strong class="block text-xl font-extrabold sm:text-2xl">{{ number_format($storeSummary['items_sold']) }}</strong><span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 sm:text-xs" data-i18n="storeProof.items">Items delivered</span></div>
                    </div>
                    <div class="flex items-center gap-3 lg:px-6 lg:last:pr-0">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-500/15 text-violet-600 dark:text-violet-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 8 4-8 4-8-4 8-4Z"/><path d="m4 12 8 4 8-4M4 17l8 4 8-4"/></svg></span>
                        <div><strong class="block text-xl font-extrabold sm:text-2xl">{{ number_format($storeSummary['products_count']) }}</strong><span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 sm:text-xs" data-i18n="storeProof.products">Products available</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Match the top-selling ranking with the same open, standard storefront structure. --}}
    <section class="overflow-hidden border-b border-slate-200 bg-white py-10 dark:border-slate-800 dark:bg-black sm:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- Loop the ten highest-selling products in compact 175 by 165 pixel ranking cards. --}}
            <div class="mb-6 flex items-end justify-between gap-4 border-b border-slate-200 pb-4 dark:border-slate-800">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-blue-600 dark:text-blue-400">Top 10 products</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-950 dark:text-white sm:text-2xl">Most ordered right now</h2>
                </div>
                <a href="#product-catalogue" class="hidden items-center gap-2 text-xs font-bold text-[#173f88] transition-colors hover:text-blue-600 dark:text-blue-300 sm:inline-flex">View all products <span aria-hidden="true">→</span></a>
            </div>
            <div data-top-seller-marquee class="top-seller-marquee overflow-hidden py-1">
                <div class="top-seller-track flex w-max">
                    @foreach ([false, true] as $isDuplicate)
                        <div class="flex shrink-0 gap-3 pr-3" @if ($isDuplicate) aria-hidden="true" @endif>
                            @foreach ($topSellers as $topSeller)
                                @php($topSellerText = $topSeller->translation())
                                <a href="{{ route('products.show', $topSeller) }}" class="group relative flex h-[165px] w-[175px] shrink-0 flex-col gap-2 border border-slate-200 bg-white p-3 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#173f88] hover:shadow-md dark:border-slate-800 dark:bg-[#0d1015] dark:hover:border-blue-500">
                                    <span class="absolute left-3 top-3 z-10 grid h-7 min-w-7 shrink-0 place-items-center bg-[#173f88] px-1.5 text-[10px] font-extrabold text-white">#{{ $loop->iteration }}</span>
                                    <img src="{{ $topSeller->primaryImage()?->url() }}" alt="{{ $topSellerText?->name }}" class="h-[88px] w-full shrink-0 bg-slate-50 object-contain p-1 dark:bg-slate-900">
                                    <span class="min-w-0 flex-1">
                                        <strong class="block truncate text-xs text-slate-900 dark:text-white">{{ $topSellerText?->name }}</strong>
                                        <span class="mt-1 flex items-center justify-between gap-2 text-[10px] font-bold"><span class="text-rose-500">${{ $topSeller->price }}</span><span class="text-slate-500 dark:text-slate-400">{{ number_format($topSeller->sold_quantity) }} sold</span></span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </section>

    {{-- Match storefront filtering to the compact operational dashboard style. --}}
    <section class="border-y border-slate-200 bg-[#f4f6f9] py-6 dark:border-slate-800 dark:bg-black">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('products.index') }}" data-storefront-filter data-product-filter-form class="border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-[#0e1113]">
                <div class="mb-4 flex flex-col justify-between gap-1 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="text-sm font-bold text-slate-950 dark:text-white" data-i18n="filter.title">Filter products</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400" data-i18n="filter.hint">Choose one or more categories</p>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.2fr_1fr_.55fr_.55fr_.7fr]">
                    <label class="grid gap-1 text-[11px] font-bold text-slate-700 dark:text-slate-200">
                        <span data-i18n="filter.categoryLabel">Categories</span>
                        <select id="category-filter" name="categories[]" multiple autocomplete="off" aria-label="Product categories">
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected(in_array($category->slug, $filters['categories'] ?? [], true))>{{ $category->translation()?->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-[11px] font-bold text-slate-700 dark:text-slate-200">
                        <span data-i18n="filter.searchLabel">Search</span>
                        <span class="relative block">
                            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                            <input id="product-search" name="search" value="{{ $filters['search'] ?? '' }}" type="search" data-i18n-placeholder="filter.searchPlaceholder" placeholder="Search products..." class="h-9 w-full border border-slate-200 bg-white pl-9 pr-3 text-xs font-medium outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-[#0e1113] dark:text-white">
                        </span>
                    </label>
                    <label class="grid gap-1 text-[11px] font-bold text-slate-700 dark:text-slate-200"><span>Min price</span><input name="min_price" type="number" min="0" step="0.01" value="{{ $filters['min_price'] ?? '' }}" placeholder="$0" class="h-9 border border-slate-200 bg-white px-3 text-xs outline-none focus:border-[#173f88] dark:border-slate-700 dark:bg-[#0e1113]"></label>
                    <label class="grid gap-1 text-[11px] font-bold text-slate-700 dark:text-slate-200"><span>Max price</span><input name="max_price" type="number" min="0" step="0.01" value="{{ $filters['max_price'] ?? '' }}" placeholder="Any" class="h-9 border border-slate-200 bg-white px-3 text-xs outline-none focus:border-[#173f88] dark:border-slate-700 dark:bg-[#0e1113]"></label>
                    <label class="grid gap-1 text-[11px] font-bold text-slate-700 dark:text-slate-200"><span>Sort by</span><select name="sort" class="h-9 border border-slate-200 bg-white px-2 text-xs outline-none focus:border-[#173f88] dark:border-slate-700 dark:bg-[#0e1113]"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option><option value="best_selling" @selected(($filters['sort'] ?? '') === 'best_selling')>Best selling</option><option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>Price: low to high</option><option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>Price: high to low</option></select></label>
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300"><input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'] ?? false) class="size-4 accent-[#173f88]">In-stock products only</label><div class="flex gap-2"><a href="{{ route('products.index') }}#product-catalogue" class="flex h-9 items-center border border-slate-200 px-4 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300">Reset</a><button class="h-9 bg-[#173f88] px-5 text-xs font-bold text-white">Apply filters</button></div></div>
            </form>
        </div>
    </section>

    {{-- Keep two product cards per row on phones and the existing desktop column flow. --}}
    <section id="product-catalogue" class="scroll-mt-24 bg-[#f7f9fc] py-12 dark:bg-black sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div data-product-grid class="grid grid-cols-2 items-stretch gap-2 sm:gap-3 lg:grid-cols-3 xl:grid-cols-5 xl:gap-3.5">
                @foreach ($products as $product)
                    <x-product-card :product="$product" :index="$loop->index" :wishlisted="$wishlistedProductIds->contains($product->id)" :in-cart="$cartProductIds->contains($product->id)" />
                @endforeach
            </div>
            <div data-empty-state class="{{ $products->isEmpty() ? '' : 'hidden' }} border border-dashed border-slate-300 bg-white px-6 py-16 text-center font-semibold text-slate-500 dark:border-slate-700 dark:bg-[#0e1113] dark:text-slate-400" data-i18n="products.empty">No products match your filters.</div>

            {{-- Paginate the database-backed catalogue while preserving active filters. --}}
            <div class="mt-8"><x-pagination :paginator="$products" /></div>
        </div>
    </section>
@endsection
