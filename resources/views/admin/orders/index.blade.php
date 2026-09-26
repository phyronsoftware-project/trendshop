@extends('admin.layouts.app')
@section('title','Orders') @section('breadcrumb','Sales / Orders')
@section('content')
<div class="mb-5"><h1 class="text-xl font-bold">Orders</h1><p class="mt-1 text-xs text-slate-500">Review purchases and update fulfillment status.</p></div>
{{-- Filter fulfillment and payment states independently while preserving date-group pagination. --}}
<form method="GET" class="mb-5 grid gap-3 border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-[1fr_190px_190px_auto]"><input name="search" value="{{ request('search') }}" placeholder="order / recipient" class="h-9 border border-slate-200 px-3 text-xs"><select name="status" class="h-9 border border-slate-200 px-3 text-xs"><option value="">All statuses</option>@foreach(['pending','confirmed','processing','shipped','delivered','cancelled','refunded'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select><select name="payment_status" class="h-9 border border-slate-200 px-3 text-xs"><option value="">All payments</option>@foreach(['unpaid','pending','paid','failed','refunded'] as $paymentStatus)<option value="{{ $paymentStatus }}" @selected(request('payment_status') === $paymentStatus)>{{ ucfirst($paymentStatus) }}</option>@endforeach</select><button class="h-9 bg-[#173f88] px-5 text-xs font-bold text-white">Filter</button></form>
{{-- Group orders beneath a single Today, Yesterday or calendar-date label. --}}
<div class="grid gap-6">
    @forelse($orderGroups as $dateLabel => $group)
        <section>
            <div class="mb-2 flex items-center gap-3"><h2 class="text-sm font-bold text-slate-800">{{ $dateLabel }}</h2><span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $group->count() }}</span><span class="h-px flex-1 bg-slate-200"></span></div>
            <div class="overflow-hidden border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[920px] text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase text-slate-500"><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Items</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                        <tbody>
                            @foreach($group as $order)
                                <tr id="order-{{ $order->id }}" class="scroll-mt-20 border-t border-slate-100 transition-colors {{ (int) request('focus_order') === $order->id ? 'bg-blue-50 outline-2 -outline-offset-2 outline-[#173f88]' : 'hover:bg-slate-50' }}">
                                    <td class="px-4 py-3"><a data-admin-load href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#173f88] hover:underline">{{ $order->order_number }}</a><span class="mt-1 block text-[10px] font-normal text-slate-400">{{ ucfirst($order->payment_status) }}</span></td>
                                    <td class="px-4 py-3">{{ $order->user?->name ?? $order->recipient_name }}<span class="mt-1 block text-[10px] text-slate-400">{{ $order->recipient_phone }}</span></td>
                                    <td class="px-4 py-3">{{ $order->items->sum('quantity') }}</td>
                                    <td class="px-4 py-3 font-bold text-rose-500">${{ $order->grand_total }}</td>
                                    {{-- Display the UTC order timestamp in Cambodia local time. --}}
                                    <td class="px-4 py-3">{{ ($order->placed_at ?? $order->created_at)->timezone(config('app.display_timezone'))->format('h:i A') }}</td>
                                    {{-- Distinguish each fulfillment state with a consistent semantic color. --}}
                                    <td class="px-4 py-3"><span @class(['inline-flex px-2 py-1 text-[10px] font-bold uppercase tracking-wide', 'bg-amber-100 text-amber-800' => $order->status === 'pending', 'bg-blue-100 text-blue-800' => $order->status === 'confirmed', 'bg-violet-100 text-violet-800' => $order->status === 'processing', 'bg-cyan-100 text-cyan-800' => $order->status === 'shipped', 'bg-emerald-100 text-emerald-800' => $order->status === 'delivered', 'bg-red-100 text-red-700' => $order->status === 'cancelled', 'bg-slate-200 text-slate-700' => $order->status === 'refunded'])>{{ $order->status }}</span></td>
                                    <td class="px-4 py-3 text-right"><a data-admin-load href="{{ route('admin.orders.show', $order) }}" class="inline-flex h-8 items-center border border-[#173f88] px-3 text-[10px] font-bold text-[#173f88]">View order</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @empty
        <div class="border border-dashed border-slate-300 bg-white p-10 text-center text-xs font-semibold text-slate-500">No orders found.</div>
    @endforelse
</div>
{{-- Match the product-list footer while counting complete order-date groups. --}}
<div class="mt-5 flex flex-col items-center justify-between gap-3 border border-slate-200 bg-white px-4 py-3 sm:flex-row">
    <p class="text-[11px] font-semibold text-slate-500">Showing {{ $datePages->firstItem() ?? 0 }}–{{ $datePages->lastItem() ?? 0 }} of {{ $datePages->total() }} date groups</p>
    <x-pagination :paginator="$datePages" />
</div>
@endsection
