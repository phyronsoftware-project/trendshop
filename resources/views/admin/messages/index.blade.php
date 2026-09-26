@extends('admin.layouts.app')

@section('title', 'Messages')
@section('breadcrumb', 'Messages')

@section('content')
    {{-- Manage customer conversations in the dashboard two-panel inbox. --}}
    <div class="mb-5 flex items-center justify-between gap-3">
        <div><h1 class="text-xl font-bold text-slate-950">Customer messages</h1><p class="mt-1 text-xs text-slate-500">Discuss products and purchases with customers.</p></div>
        <span class="border border-slate-200 bg-white px-3 py-2 text-[11px] font-semibold">Updates automatically</span>
    </div>

    {{-- Keep the desktop inbox at 630px and scroll each panel internally. --}}
    <section
        data-chat-shell
        data-chat-admin
        data-chat-conversations-url="{{ route('admin.chat.conversations.index') }}"
        class="grid overflow-hidden border border-slate-200 bg-white shadow-sm lg:h-[630px] lg:grid-cols-[300px_1fr]"
    >
        <aside class="flex min-h-0 flex-col border-b border-slate-200 lg:border-b-0 lg:border-r">
            <div class="border-b border-slate-200 p-3">
                <label class="relative block">
                    <span class="sr-only">Search conversations</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input data-chat-conversation-search type="search" placeholder="Search customers..." class="h-9 w-full border border-slate-200 pl-9 pr-3 text-xs outline-none focus:border-[#173f88]">
                </label>
            </div>
            <div data-chat-conversation-list class="max-h-64 overflow-y-auto lg:max-h-none lg:flex-1">
                @include('admin.messages._conversation-list', ['activeConversationId' => $selectedConversation?->id ?? 0])
            </div>
        </aside>

        <div class="flex min-h-[515px] min-w-0 flex-col lg:min-h-0">
            <header class="flex h-16 items-center gap-3 border-b border-slate-200 px-4">
                <img data-chat-active-avatar src="{{ $selectedConversation?->user->avatarUrl() ?? asset('logo_web/me.jpg') }}" alt="" class="size-9 rounded-full object-cover {{ $selectedConversation ? '' : 'invisible' }}">
                <div class="min-w-0 flex-1">
                    <h2 data-chat-active-name class="truncate text-sm font-bold text-slate-950">{{ $selectedConversation?->user->name ?? 'Select a conversation' }}</h2>
                    <p data-chat-active-email class="truncate text-[11px] text-slate-500">{{ $selectedConversation?->user->email ?? 'Choose a customer from the left panel.' }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600"><span class="size-2 rounded-full bg-emerald-500"></span>Support</span>
            </header>

            {{-- Keep the admin conversation canvas consistently white. --}}
            <div data-chat-messages class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto bg-white p-4">
                @forelse($messages as $message)
                    <x-chat.message :message="$message" :mine="$message->sender?->role === 'admin'" />
                @empty
                    <div data-chat-empty class="m-auto text-center text-xs text-slate-500">{{ $selectedConversation ? 'No messages in this conversation.' : 'Select a customer conversation.' }}</div>
                @endforelse
            </div>

            <x-chat.composer :action="$selectedConversation ? route('admin.chat.messages.store', $selectedConversation) : null" />
        </div>
    </section>
@endsection
