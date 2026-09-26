@extends('admin.layouts.app')

@section('title', 'Notifications')
@section('breadcrumb', 'Notifications')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-bold">Notifications</h1>
        <p class="mt-1 text-xs text-slate-500">New customer orders and system activity.</p>
    </div>

    {{-- Group notifications under one Facebook-style date label instead of repeating dates in every row. --}}
    <div class="grid gap-6">
        @forelse($notificationGroups as $dateLabel => $group)
            <section>
                <div class="mb-2 flex items-center gap-3">
                    <h2 class="text-sm font-bold text-slate-800">{{ $dateLabel }}</h2>
                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $group->count() }}</span>
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>

                <div class="overflow-hidden border border-slate-200 bg-white shadow-sm">
                    @foreach($group as $notification)
                        <form method="POST" action="{{ route('admin.notifications.open', $notification) }}" class="border-b border-slate-100 last:border-b-0">
                            @csrf
                            <button type="submit" class="group flex w-full items-center gap-4 px-4 py-3 text-left transition-colors hover:bg-blue-50 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/60' }}">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-[#173f88] text-white shadow-sm">
                                    <x-admin.icon :name="$notification->order ? 'order' : 'bell'" class="size-[18px]" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <strong class="truncate text-xs text-slate-900">{{ $notification->title }}</strong>
                                        @if(! $notification->read_at)
                                            <span class="size-2 shrink-0 rounded-full bg-blue-600" title="Unread"></span>
                                        @endif
                                    </span>
                                    <span class="mt-0.5 block truncate text-[11px] text-slate-500">{{ $notification->message }}</span>
                                    @if($notification->order)
                                        <span class="mt-1 inline-flex bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-[#173f88]">{{ $notification->order->order_number }}</span>
                                    @endif
                                </span>

                                <span class="flex shrink-0 items-center gap-2 text-[10px] font-semibold text-slate-400">
                                    {{-- Display the UTC notification timestamp in Cambodia local time. --}}
                                    {{ $notification->created_at->timezone(config('app.display_timezone'))->format('h:i A') }}
                                    <span class="grid size-7 place-items-center rounded-full text-[#173f88] transition-colors group-hover:bg-white"><x-admin.icon name="chevron-left" class="size-3.5 rotate-180" /></span>
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="border border-dashed border-slate-300 bg-white p-10 text-center">
                <span class="mx-auto grid size-11 place-items-center rounded-full bg-slate-100 text-slate-400"><x-admin.icon name="bell" class="size-5" /></span>
                <p class="mt-3 text-xs font-semibold text-slate-500">No notifications.</p>
            </div>
        @endforelse
    </div>

    {{-- Show the complete result range and reuse the compact shared paginator. --}}
    <div class="mt-5 flex flex-col items-center justify-between gap-3 border border-slate-200 bg-white px-4 py-3 sm:flex-row">
        <p class="text-[11px] font-semibold text-slate-500">
            Showing {{ $datePages->firstItem() ?? 0 }}–{{ $datePages->lastItem() ?? 0 }} of {{ $datePages->total() }} date groups
        </p>
        {{-- Keep page controls visible even when all notifications fit on one page. --}}
        <x-pagination :paginator="$datePages" always />
    </div>
@endsection
