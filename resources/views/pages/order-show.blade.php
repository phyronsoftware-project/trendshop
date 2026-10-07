@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number.' — TrendShop')

@section('content')
<section class="min-h-[65vh] bg-[#f7f9fc] py-10 dark:bg-black">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><a href="{{ route('profile') }}#orders" class="text-xs font-bold text-[#173f88] dark:text-blue-300">← Order history</a><h1 class="mt-2 text-2xl font-bold sm:text-3xl">Order {{ $order->order_number }}</h1><p class="mt-1 text-sm text-slate-500">Placed {{ ($order->placed_at ?? $order->created_at)->timezone((string) config('app.display_timezone'))->format('d M Y, h:i A') }}</p></div>
            <span class="border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold uppercase tracking-wider text-[#173f88] dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">{{ $order->status }}</span>
        </div>

        {{-- Show a clear customer-facing fulfillment timeline. --}}
        @php($currentStep = array_search($order->status, $order->progressStatuses(), true))
        <ol class="mt-8 grid grid-cols-5 gap-1" aria-label="Order progress">
            @foreach($order->progressStatuses() as $index => $status)
                @php($complete = is_int($currentStep) && $index <= $currentStep)
                <li class="grid gap-2 text-center"><span class="h-1 {{ $complete ? 'bg-[#173f88]' : 'bg-slate-200 dark:bg-slate-800' }}"></span><span class="text-[10px] font-bold uppercase sm:text-xs {{ $complete ? 'text-[#173f88] dark:text-blue-300' : 'text-slate-400' }}">{{ $status }}</span></li>
            @endforeach
        </ol>
        @if(in_array($order->status, ['cancelled', 'refunded'], true))<p class="mt-4 border-l-4 border-red-400 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:bg-red-950/20 dark:text-red-300">This order is {{ $order->status }}.</p>@endif

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_330px]">
            <div class="grid content-start gap-5">
                <section class="overflow-hidden border border-slate-200 bg-white dark:border-slate-800 dark:bg-[#0e1113]"><div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 class="font-bold">Products</h2></div><div class="divide-y divide-slate-100 dark:divide-slate-800">@foreach($order->items as $item)<article class="flex gap-4 p-5">@if($item->product_image_path)<img src="{{ str_starts_with($item->product_image_path, 'http') || str_starts_with($item->product_image_path, '/') ? $item->product_image_path : asset($item->product_image_path) }}" alt="{{ $item->product_name }}" class="size-20 border border-slate-200 object-contain dark:border-slate-700">@endif<div class="min-w-0 flex-1"><h3 class="font-bold">{{ $item->product_name }}</h3><p class="mt-1 text-xs text-slate-500">SKU: {{ $item->product_sku }}</p><div class="mt-3 flex justify-between text-sm"><span>{{ $item->quantity }} × ${{ $item->unit_price }}</span><strong>${{ $item->line_total }}</strong></div></div></article>@endforeach</div></section>
                <section class="border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-[#0e1113]"><h2 class="font-bold">Delivery address</h2><div class="mt-3 grid gap-1 text-sm text-slate-600 dark:text-slate-300"><strong class="text-slate-900 dark:text-white">{{ $order->recipient_name }}</strong><span>{{ $order->recipient_phone }}</span><p>{{ collect([$order->delivery_address_line_1, $order->delivery_address_line_2, $order->delivery_commune, $order->delivery_district, $order->delivery_city_province, $order->delivery_postal_code])->filter()->join(', ') }}</p></div>@if($order->customer_note)<p class="mt-4 border-l-3 border-amber-400 bg-amber-50 px-3 py-2 text-xs dark:bg-amber-950/20"><strong>Order note:</strong> {{ $order->customer_note }}</p>@endif</section>
            </div>

            <aside class="grid content-start gap-5">
                <section class="border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-[#0e1113]"><h2 class="font-bold">Order summary</h2><dl class="mt-4 grid gap-2 text-sm"><div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>${{ $order->subtotal }}</dd></div><div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd>-${{ $order->discount_total }}</dd></div><div class="flex justify-between"><dt class="text-slate-500">Delivery</dt><dd>${{ $order->delivery_fee }}</dd></div><div class="flex justify-between border-t border-slate-200 pt-3 text-base font-bold dark:border-slate-700"><dt>Total</dt><dd class="text-rose-500">${{ $order->grand_total }}</dd></div></dl><div class="mt-4 border-t border-slate-200 pt-4 text-xs dark:border-slate-700"><p><strong>Payment:</strong> {{ str($order->payments->last()?->provider ?? 'cash_on_delivery')->replace('_', ' ')->title() }}</p><p class="mt-1"><strong>Status:</strong> {{ ucfirst($order->payment_status) }}</p></div></section>
                @if($order->tracking_number || $order->shipping_carrier)<section class="border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-[#0e1113]"><h2 class="font-bold">Shipment tracking</h2><dl class="mt-3 grid gap-2 text-sm"><div><dt class="text-xs text-slate-500">Carrier</dt><dd class="font-bold">{{ $order->shipping_carrier ?: 'Not specified' }}</dd></div><div><dt class="text-xs text-slate-500">Tracking number</dt><dd class="break-all font-bold text-[#173f88] dark:text-blue-300">{{ $order->tracking_number ?: 'Pending' }}</dd></div></dl></section>@endif
                <section class="grid gap-2"><form method="POST" action="{{ route('orders.reorder', $order) }}">@csrf<button class="h-11 w-full border border-[#173f88] font-bold text-[#173f88] transition-colors hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-950/20">Buy again</button></form>@if($order->status === 'pending' && $order->payment_status !== 'paid')<form method="POST" action="{{ route('orders.cancel', $order) }}">@csrf @method('PATCH')<button class="h-11 w-full border border-red-300 font-bold text-red-600 transition-colors hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/20">Cancel order</button></form>@endif</section>
            </aside>
        </div>
    </div>
</section>
@endsection
