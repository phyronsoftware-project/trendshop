@props(['message', 'mine' => false])

{{-- Render one escaped text or private media message on the correct side. --}}
<article data-chat-message-id="{{ $message->id }}" class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
    <div class="max-w-[82%] sm:max-w-[70%]">
        {{-- Media messages render without a colored outer message box. --}}
        <div @class([
            'border px-3.5 py-2.5 shadow-sm' => $message->type === 'text',
            'border-[#173f88] bg-[#173f88] text-white' => $message->type === 'text' && $mine,
            'border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-[#141820] dark:text-slate-100' => $message->type === 'text' && ! $mine,
        ])>
            @if($message->type === 'image')
                <a href="{{ route('chat.attachments.show', $message) }}" target="_blank" rel="noopener" @class(['block overflow-hidden', 'mb-2' => filled($message->body)])>
                    <img src="{{ route('chat.attachments.show', $message) }}" alt="{{ $message->attachment_name ?? 'Chat image' }}" class="h-auto max-h-72 w-auto max-w-full object-contain">
                </a>
            @elseif($message->type === 'audio')
                <audio controls preload="metadata" class="h-10 w-full min-w-64" src="{{ route('chat.attachments.show', $message) }}"></audio>
            @endif

            @if(filled($message->body))
                <p @class([
                    'whitespace-pre-wrap break-words text-sm leading-6',
                    'text-slate-800 dark:text-slate-100' => $message->type !== 'text',
                ])>{{ $message->body }}</p>
            @endif
        </div>
        {{-- Display chat timestamps with a type-safe configured timezone. --}}
        <div class="mt-1 flex items-center gap-2 px-1 text-[10px] text-slate-400 {{ $mine ? 'justify-end' : 'justify-start' }}">
            <span>{{ $message->sender?->name }}</span>
            <time datetime="{{ $message->created_at?->toIso8601String() }}">{{ $message->created_at?->timezone((string) config('app.display_timezone'))->format('h:i A') }}</time>
        </div>
    </div>
</article>
