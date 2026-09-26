@extends('admin.layouts.app')
@section('title', 'Dashboard') @section('breadcrumb', 'Overview')
@section('content')
{{-- Use the Cambodia calendar date in the dashboard header. --}}
<div class="mb-5 flex items-center justify-between"><div><h1 class="text-xl font-bold text-slate-950">Dashboard</h1><p class="mt-1 text-xs text-slate-500">TrendShop sales and catalogue overview</p></div><span class="border border-slate-200 bg-white px-3 py-2 text-[11px] font-semibold">{{ now(config('app.display_timezone'))->format('d M Y') }}</span></div>
{{-- Summarize the most important database totals in compact cards. --}}
<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
@foreach([
    ['Products', $stats['products'], '#2563eb', route('admin.products.index')],
    ['Customers', $stats['customers'], '#7c3aed', route('admin.customers.index')],
    ['Orders', $stats['orders'], '#0891b2', route('admin.orders.index')],
    ['Paid revenue', '$'.number_format($stats['revenue'], 2), '#16a34a', route('admin.orders.index', ['payment_status' => 'paid'])],
] as [$label, $value, $color, $url])
    <a data-admin-load href="{{ $url }}" aria-label="View {{ strtolower($label) }}" class="group border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#173f88] hover:shadow-md focus:outline-2 focus:outline-offset-2 focus:outline-[#173f88]">
        <div class="flex items-center justify-between"><span class="text-xs font-semibold text-slate-500 group-hover:text-[#173f88]">{{ $label }}</span><span class="size-2" style="background:{{ $color }}"></span></div>
        <strong class="mt-3 block text-2xl text-slate-950">{{ $value }}</strong>
    </a>
@endforeach
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-[1.5fr_.75fr]">
    <section class="overflow-hidden border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-200 px-4 py-3"><h2 class="text-sm font-bold">Recent orders</h2><a data-admin-load href="{{ route('admin.orders.index') }}" class="text-[11px] font-bold text-[#173f88]">View all</a></div><div class="overflow-x-auto"><table class="w-full text-left text-xs"><thead class="bg-slate-50 text-[10px] uppercase text-slate-500"><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Total</th></tr></thead><tbody>
        {{-- Make every recent-order cell open that order's overview. --}}
        @foreach($recentOrders as $order)
            <tr class="group border-t border-slate-100 hover:bg-slate-50">
                <td class="p-0 font-semibold"><a data-admin-load href="{{ route('admin.orders.show', $order) }}" class="block px-4 py-3 text-[#173f88] group-hover:underline">{{ $order->order_number }}</a></td>
                <td class="p-0"><a data-admin-load href="{{ route('admin.orders.show', $order) }}" class="block px-4 py-3">{{ $order->user?->name ?? $order->recipient_name }}</a></td>
                {{-- Match the semantic status colors used on the full orders page. --}}
                <td class="p-0"><a data-admin-load href="{{ route('admin.orders.show', $order) }}" class="block px-4 py-3"><span @class(['inline-flex px-2 py-1 text-[10px] font-bold uppercase tracking-wide', 'bg-amber-100 text-amber-800' => $order->status === 'pending', 'bg-blue-100 text-blue-800' => $order->status === 'confirmed', 'bg-violet-100 text-violet-800' => $order->status === 'processing', 'bg-cyan-100 text-cyan-800' => $order->status === 'shipped', 'bg-emerald-100 text-emerald-800' => $order->status === 'delivered', 'bg-red-100 text-red-700' => $order->status === 'cancelled', 'bg-slate-200 text-slate-700' => $order->status === 'refunded'])>{{ $order->status }}</span></a></td>
                <td class="p-0 font-bold"><a data-admin-load href="{{ route('admin.orders.show', $order) }}" class="block px-4 py-3">${{ $order->grand_total }}</a></td>
            </tr>
        @endforeach
    </tbody></table></div></section>
    <section class="border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-200 px-4 py-3"><h2 class="text-sm font-bold">Low stock</h2></div><div class="divide-y divide-slate-100">@forelse($lowStockProducts as $product)<a data-admin-load href="{{ route('admin.products.edit',$product) }}" class="flex items-center justify-between px-4 py-3 hover:bg-slate-50"><span class="text-xs font-semibold">{{ $product->translation('en')?->name }}</span><span class="text-[10px] font-bold text-red-500">{{ $product->stock_quantity }} left</span></a>@empty<p class="p-5 text-xs text-slate-500">Stock levels are healthy.</p>@endforelse</div></section>
</div>
@endsection
