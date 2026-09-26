@extends('layouts.storefront')

@section('title', 'Contact support — TrendShop')

@section('content')
    {{-- Give each signed-in customer one continuous private support conversation. --}}
    <section class="bg-[#f7f9fc] py-8 dark:bg-black sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div
                data-chat-shell
                data-chat-messages-url="{{ route('chat.messages.index') }}"
                class="overflow-hidden border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#0e1113]"
            >
                <header class="flex items-center gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800 sm:px-5">
                    <span class="grid size-10 shrink-0 place-items-center bg-[#173f88] text-white">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/><path d="M8 9h8M8 13h5"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-sm font-bold text-slate-950 dark:text-white" data-i18n="chat.title">TrendShop Support</h1>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400" data-i18n="chat.subtitle">Discuss products and get help with your purchase.</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"><span class="size-2 rounded-full bg-emerald-500"></span><span data-i18n="chat.available">Available</span></span>
                </header>

                <div data-chat-messages class="flex h-[58vh] min-h-[420px] flex-col gap-3 overflow-y-auto bg-slate-50/70 p-4 dark:bg-black/20 sm:p-5">
                    @forelse($messages as $message)
                        <x-chat.message :message="$message" :mine="$message->sender_id === $customer->id" />
                    @empty
                        <div data-chat-empty class="m-auto max-w-sm text-center">
                            <span class="mx-auto grid size-12 place-items-center rounded-full bg-blue-50 text-[#173f88] dark:bg-blue-950/40 dark:text-blue-300"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg></span>
                            <h2 class="mt-3 text-sm font-bold text-slate-900 dark:text-white" data-i18n="chat.emptyTitle">Start a conversation</h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400" data-i18n="chat.emptyText">Send a message, image or voice note to the TrendShop team.</p>
                        </div>
                    @endforelse
                </div>

                <x-chat.composer :action="route('chat.messages.store')" />
            </div>
        </div>
    </section>
@endsection
