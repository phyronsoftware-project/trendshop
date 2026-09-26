{{-- Keep the newest customer conversations and unread counts visible in the inbox. --}}
@forelse($conversations as $conversation)
    @php($isActive = (int) $activeConversationId === $conversation->id)
    <button
        type="button"
        data-chat-conversation="{{ $conversation->id }}"
        data-chat-conversation-active="{{ $isActive ? 'true' : 'false' }}"
        data-chat-messages-url="{{ route('admin.chat.messages.index', $conversation) }}"
        data-chat-send-url="{{ route('admin.chat.messages.store', $conversation) }}"
        @class([
            'flex w-full items-start gap-3 border-b border-slate-100 px-3 py-3 text-left transition-colors hover:bg-blue-50',
            'bg-blue-50' => $isActive,
            'bg-white' => ! $isActive,
        ])
    >
        <img src="{{ $conversation->user->avatarUrl() }}" alt="{{ $conversation->user->name }}" class="size-9 shrink-0 rounded-full object-cover">
        <span class="min-w-0 flex-1">
            <span class="flex items-center justify-between gap-2">
                <strong class="truncate text-xs text-slate-900">{{ $conversation->user->name }}</strong>
                {{-- Pass the configured display timezone to Carbon as a string. --}}
                <time class="shrink-0 text-[10px] text-slate-400">{{ $conversation->last_message_at?->timezone((string) config('app.display_timezone'))->format('h:i A') }}</time>
            </span>
            <span class="mt-1 flex items-center justify-between gap-2">
                <span class="truncate text-[11px] text-slate-500">
                    @if($conversation->latestMessage?->type === 'image')
                        📷 Image
                    @elseif($conversation->latestMessage?->type === 'audio')
                        🎙 Voice message
                    @else
                        {{ $conversation->latestMessage?->body }}
                    @endif
                </span>
                @if($conversation->unread_messages_count > 0)
                    <span class="grid min-w-5 shrink-0 place-items-center rounded-full bg-[#173f88] px-1.5 py-0.5 text-[9px] font-bold text-white">{{ $conversation->unread_messages_count }}</span>
                @endif
            </span>
        </span>
    </button>
@empty
    <div class="p-6 text-center text-xs text-slate-500">No customer messages yet.</div>
@endforelse
