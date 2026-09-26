@props(['action' => null])

{{-- Send text, emoji, one image or a browser-recorded voice message. --}}
<form data-chat-form action="{{ $action ?? '#' }}" method="POST" enctype="multipart/form-data" novalidate class="border-t border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-[#0e1113]">
    @csrf
    <div data-chat-feedback class="mb-2 hidden border-l-3 border-red-500 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 dark:bg-red-950/30 dark:text-red-300"></div>
    {{-- Preview every selected image with its own removal control. --}}
    <div data-chat-image-previews class="mb-2 hidden flex-wrap gap-2"></div>
    <div data-chat-audio-preview class="mb-2 hidden items-center gap-2 text-xs font-semibold text-[#173f88] dark:text-blue-200">
        <span data-chat-audio-name></span>
        <button type="button" data-chat-audio-remove class="grid size-5 place-items-center rounded-full bg-slate-100 text-sm text-slate-600 hover:bg-red-50 hover:text-red-600" aria-label="Remove voice message">×</button>
    </div>
    <div class="relative flex items-end gap-2">
        <div class="relative flex shrink-0 items-center gap-1">
            <button type="button" data-chat-emoji-toggle data-i18n-aria-label="chat.chooseEmoji" class="grid size-10 place-items-center border border-slate-200 text-lg transition-colors hover:border-[#173f88] dark:border-slate-700" aria-label="Choose emoji">☺</button>
            <div data-chat-emoji-panel class="absolute bottom-12 left-0 z-20 hidden w-56 grid-cols-8 gap-1 border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-[#141820]">
                @foreach(['😀','😂','😍','😊','🙏','👍','❤️','🎉','😢','😮','🔥','✅','🛍️','📦','💳','🚚'] as $emoji)
                    <button type="button" data-chat-emoji="{{ $emoji }}" class="grid size-6 place-items-center text-base hover:bg-slate-100 dark:hover:bg-slate-800">{{ $emoji }}</button>
                @endforeach
            </div>
        </div>
        <label data-i18n-aria-label="chat.uploadImage" class="grid size-10 shrink-0 cursor-pointer place-items-center border border-slate-200 text-slate-600 transition-colors hover:border-[#173f88] hover:text-[#173f88] dark:border-slate-700 dark:text-slate-300" aria-label="Upload image">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 15-5-5L5 20"/></svg>
            <input data-chat-file name="attachments[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" multiple>
        </label>
        <button type="button" data-chat-record data-i18n-aria-label="chat.recordVoice" class="grid size-10 shrink-0 place-items-center border border-slate-200 text-slate-600 transition-colors hover:border-red-400 hover:text-red-500 dark:border-slate-700 dark:text-slate-300" aria-label="Record voice message">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3M9 21h6"/></svg>
        </button>
        <textarea data-chat-body name="body" rows="1" maxlength="5000" data-i18n-placeholder="chat.placeholder" placeholder="Write a message..." class="max-h-32 min-h-10 flex-1 resize-none border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-[#141820] dark:text-white"></textarea>
        <button data-chat-send type="submit" class="grid size-10 shrink-0 place-items-center bg-[#173f88] text-white transition-colors hover:bg-[#0a2f6b] disabled:cursor-not-allowed disabled:opacity-40" @disabled($action === null) aria-label="Send message">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg>
        </button>
    </div>
    <p data-chat-recording-status class="mt-2 hidden text-[11px] font-semibold text-red-500">Recording… click the microphone again to stop.</p>
</form>
