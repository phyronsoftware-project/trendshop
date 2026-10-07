@extends('layouts.storefront')

@section('title', ($product->translation()?->meta_title ?: $product->translation()?->name ?: 'Product').' — TrendShop')
@section('meta_description', $product->translation()?->meta_description ?: $product->translation()?->short_description ?: 'TrendShop product details')

@section('content')
@php($text = $product->translation())
@php($available = ! $product->track_stock || $product->stock_quantity > 0)
<section class="bg-[#f7f9fc] py-10 dark:bg-black">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-6 text-sm font-semibold text-slate-500"><a href="{{ route('products.index') }}">Products</a> / {{ $product->category?->translation()?->name }} / <span class="text-slate-900 dark:text-white">{{ $text?->name }}</span></nav>
        <div class="grid overflow-hidden border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#0e1113] lg:grid-cols-2">
            {{-- Keep sub-images under the main image and open a large gallery popup. --}}
            <div class="border-b border-slate-200 p-5 dark:border-slate-800 lg:border-b-0 lg:border-r">
                <button type="button" data-gallery-open class="flex aspect-square w-full items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-900"><img data-gallery-main src="{{ $product->primaryImage()?->url() }}" alt="{{ $text?->name }}" class="size-full object-contain"></button>
                <div class="mt-4 flex flex-wrap justify-center gap-3">@foreach($product->images as $image)<button type="button" data-gallery-thumbnail data-image="{{ $image->url() }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" class="size-20 border {{ $loop->first ? 'border-[#173f88]' : 'border-slate-200' }} bg-white p-1.5 transition-colors dark:bg-[#0e1113]"><img src="{{ $image->url() }}" alt="{{ $text?->name }}" class="size-full object-contain"></button>@endforeach</div>
            </div>

            {{-- Organize product information and actions into compact visual groups. --}}
            <div class="flex flex-col gap-5 p-6 sm:p-7 lg:p-8">
                <header>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600">{{ $product->category?->translation()?->name }}</p>
                    <h1 class="mt-2 text-3xl font-extrabold leading-tight text-slate-950 dark:text-white sm:text-4xl">{{ $text?->name }}</h1>
                    <div class="mt-3 flex items-center gap-3"><span @class(['rounded-full px-3 py-1.5 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $available, 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => ! $available])>{{ $available ? 'In stock' : 'Out of stock' }}</span><span class="text-sm text-slate-500">Qty: {{ $product->stock_quantity }}</span></div>
                </header>

                <div class="flex flex-wrap items-baseline gap-4 border-y border-slate-200 py-4 dark:border-slate-700"><strong class="text-4xl text-rose-500">${{ $product->price }}</strong><span class="font-bold text-slate-400 line-through">${{ $product->compare_at_price }}</span></div>
                <p class="text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $text?->description }}</p>

                <div class="grid gap-3 border-t border-slate-200 pt-5 dark:border-slate-700">
                    {{-- Keep the quantity controls inside their fixed column without overlapping the cart button. --}}
                    <form method="POST" action="{{ route('cart.store', $product) }}" novalidate data-validate-form class="grid min-w-0 items-start gap-3 sm:grid-cols-[minmax(0,150px)_minmax(0,1fr)]">
                        @csrf
                        <div class="grid min-w-0 gap-1">
                            <label class="text-xs font-bold">Quantity</label>
                            <div class="flex h-11 w-full min-w-0 items-center border border-slate-200 dark:border-slate-700">
                                <button type="button" data-quantity-decrease class="h-full w-11 shrink-0">−</button>
                                <input data-product-quantity name="quantity" value="1" min="1" max="{{ $product->track_stock ? max(1, $product->stock_quantity) : 99 }}" data-validation="required|number|min-value:1|max-value:{{ $product->track_stock ? max(1, $product->stock_quantity) : 99 }}" class="h-full w-0 min-w-0 flex-1 border-x border-slate-200 bg-transparent text-center outline-none">
                                <button type="button" data-quantity-increase data-max="{{ $product->track_stock ? max(1, $product->stock_quantity) : 99 }}" class="h-full w-11 shrink-0">+</button>
                            </div>
                            <x-form-error name="quantity" />
                        </div>
                        <button @disabled(! $available) class="h-11 w-full min-w-0 border border-[#173f88] font-bold text-[#173f88] transition-colors hover:bg-blue-50 disabled:cursor-not-allowed disabled:border-slate-300 disabled:text-slate-400 sm:mt-5 dark:text-white dark:hover:bg-slate-900">{{ $available ? 'Add to cart' : 'Out of stock' }}</button>
                    </form>
                    <form method="POST" action="{{ route('wishlist.toggle', $product) }}">@csrf<button class="h-11 w-full border border-slate-200 font-bold transition-colors hover:border-[#173f88] hover:text-[#173f88] dark:border-slate-700 dark:hover:text-blue-300">Add to wishlist</button></form>

                    @auth
                        @if($addresses->isNotEmpty())
                            @php($selectedAddress = $addresses->first())
                            <form method="POST" action="{{ route('orders.store') }}" novalidate data-validate-form class="grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input data-checkout-quantity type="hidden" name="quantity" value="1">
                                <input type="hidden" name="source" value="product">
                                <input type="hidden" name="checkout_token" value="{{ old('checkout_token', (string) \Illuminate\Support\Str::uuid()) }}">
                                <label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300">
                                    Delivery address
                                    <select data-delivery-address name="address_id" data-validation="required" class="h-11 w-full border border-slate-200 bg-transparent px-3 text-sm text-slate-800 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:text-white">
                                        @foreach($addresses as $address)
                                            @php($addressSummary = collect([$address->address_line_1, $address->address_line_2, $address->commune, $address->district, $address->city_province, $address->postal_code])->filter()->join(', '))
                                            <option value="{{ $address->id }}" data-recipient="{{ $address->recipient_name }}" data-phone="{{ $address->phone }}" data-summary="{{ $addressSummary }}" data-fee="${{ number_format($deliveryFees[$address->city_province] ?? 2, 2) }}">{{ $address->label }} — {{ $address->city_province }} (${{ number_format($deliveryFees[$address->city_province] ?? 2, 2) }})</option>
                                        @endforeach
                                    </select>
                                    <x-form-error name="address_id" />
                                </label>

                                {{-- Let the customer verify the complete saved address before ordering. --}}
                                <div data-delivery-address-preview class="grid gap-2 border border-blue-100 bg-blue-50/70 p-3 text-xs dark:border-blue-950 dark:bg-blue-950/20">
                                    <div class="flex items-center justify-between gap-3"><strong data-address-recipient>{{ $selectedAddress->recipient_name }}</strong><span data-address-fee class="font-bold text-[#173f88] dark:text-blue-300">${{ number_format($deliveryFees[$selectedAddress->city_province] ?? 2, 2) }}</span></div>
                                    <p data-address-phone class="text-slate-500">{{ $selectedAddress->phone }}</p>
                                    <p data-address-summary class="leading-5 text-slate-600 dark:text-slate-400">{{ collect([$selectedAddress->address_line_1, $selectedAddress->address_line_2, $selectedAddress->commune, $selectedAddress->district, $selectedAddress->city_province, $selectedAddress->postal_code])->filter()->join(', ') }}</p>
                                </div>
                                {{-- Collect the payment intent and optional delivery instruction with checkout. --}}
                                <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300">Payment method<select name="payment_method" data-validation="required" class="h-11 border border-slate-200 bg-transparent px-3 text-sm outline-none focus:border-[#173f88] dark:border-slate-700"><option value="cash_on_delivery">Cash on delivery</option><option value="bank_transfer">Bank transfer</option></select><x-form-error name="payment_method" /></label><label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300">Order note <span class="font-normal text-slate-400">(optional)</span><input name="customer_note" maxlength="1000" value="{{ old('customer_note') }}" placeholder="Delivery instruction" class="h-11 border border-slate-200 bg-transparent px-3 text-sm outline-none focus:border-[#173f88] dark:border-slate-700"></label></div>
                                <button @disabled(! $available) class="h-11 bg-[#173f88] font-bold text-white hover:bg-[#0a2f6b] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">{{ $available ? 'Buy now' : 'Out of stock' }}</button>
                            </form>
                        @else
                            <div class="grid gap-3 border-t border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20"><p class="text-xs font-semibold text-amber-800 dark:text-amber-300">Add a delivery address before buying this product.</p><button type="button" disabled class="h-11 cursor-not-allowed bg-slate-300 font-bold text-slate-500 dark:bg-slate-800">Buy now</button><a href="{{ route('profile') }}#addresses" class="text-center text-xs font-bold text-[#173f88] underline dark:text-blue-300">Add delivery address</a></div>
                        @endif
                    @endauth
                    @guest
                        <div class="grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700"><a href="{{ route('login') }}" class="flex h-11 items-center justify-center bg-[#173f88] font-bold text-white">Sign in to buy</a></div>
                    @endguest
                </div>
            </div>
        </div>

        {{-- Present structured product specifications when catalogue data provides them. --}}
        @if(! empty($text?->specifications))
            <section class="mt-10 border-y border-slate-200 py-8 dark:border-slate-800"><h2 class="text-xl font-bold">Product specifications</h2><dl class="mt-5 grid gap-x-8 gap-y-3 sm:grid-cols-2">@foreach($text->specifications as $label => $value)<div class="flex justify-between gap-4 border-b border-slate-200 pb-3 text-sm dark:border-slate-800"><dt class="font-semibold text-slate-500">{{ $label }}</dt><dd class="text-right font-bold">{{ is_array($value) ? implode(', ', $value) : $value }}</dd></div>@endforeach</dl></section>
        @endif

        {{-- Show two related cards on phones and retain the existing wide-screen grid. --}}
        @if($relatedProducts->isNotEmpty())
            <section class="mt-12">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold" data-i18n="productDetail.relatedTitle">Related products</h2>
                        <p class="mt-1 text-sm text-slate-500"><span data-i18n="productDetail.relatedDescription">More products you may like.</span> <strong class="text-[#173f88] dark:text-blue-300">{{ $product->category?->translation()?->name }}</strong></p>
                    </div>
                    <a href="{{ route('products.index') }}" class="text-sm font-bold text-[#173f88] dark:text-blue-300" data-i18n="productDetail.viewAll">View all products</a>
                </div>
                <div class="mt-6 grid grid-cols-2 items-stretch gap-2 sm:gap-3 lg:grid-cols-3 xl:grid-cols-5 xl:gap-3.5">
                    @foreach($relatedProducts as $related)
                        <x-product-card :product="$related" :index="$loop->index" :in-cart="$cartProductIds->contains($related->id)" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</section>

{{-- Provide previous and next controls inside the large product image popup. --}}
<div data-gallery-modal class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true"><button type="button" data-gallery-close class="absolute right-5 top-5 grid size-11 place-items-center rounded-full bg-white text-2xl text-slate-950">×</button><button type="button" data-gallery-previous class="absolute left-4 top-1/2 grid size-12 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-3xl text-slate-950">‹</button><img data-gallery-modal-image src="{{ $product->primaryImage()?->url() }}" alt="{{ $text?->name }}" class="max-h-[88vh] max-w-[88vw] object-contain"><button type="button" data-gallery-next class="absolute right-4 top-1/2 grid size-12 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-3xl text-slate-950">›</button></div>
@endsection
