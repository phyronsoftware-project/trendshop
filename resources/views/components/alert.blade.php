@props(['status' => 'info', 'message' => null])

{{-- Show compact feedback for both client actions and server flash messages. --}}
<div data-alert-container class="pointer-events-none fixed inset-x-4 top-5 z-[100] flex justify-center sm:inset-x-6">
    <div data-alert data-status="{{ $status }}" @if($message) data-initial-message="{{ $message }}" @endif role="status" aria-live="polite" class="pointer-events-none flex w-full max-w-sm translate-y-[-0.75rem] items-center gap-3 rounded-xl border px-4 py-3 text-sm font-semibold opacity-0 shadow-2xl transition-all duration-300">
        <span data-alert-icon class="grid size-7 shrink-0 place-items-center rounded-full text-sm">i</span>
        <span data-alert-text class="flex-1">Status message</span>
        <button type="button" data-alert-close class="grid size-7 shrink-0 place-items-center rounded-lg opacity-70 transition-all duration-300 hover:bg-black/5 hover:opacity-100 dark:hover:bg-white/10" aria-label="Close">×</button>
    </div>
</div>
