@props(['paginator', 'always' => false])

{{-- Render one compact pagination style across all server-backed lists. --}}
@if($always || $paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $pages = $lastPage <= 7
            ? range(1, $lastPage)
            : collect([1, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage])
                ->when($currentPage <= 4, fn ($items) => $items->merge([2, 3, 4, 5]))
                ->when($currentPage >= $lastPage - 3, fn ($items) => $items->merge([$lastPage - 4, $lastPage - 3, $lastPage - 2, $lastPage - 1]))
                ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
                ->unique()->sort()->values()->all();
        $previousVisiblePage = null;
    @endphp
    <nav class="flex flex-wrap items-center justify-center gap-1.5" role="navigation" aria-label="Pagination">
        @if($paginator->onFirstPage())
            <span class="grid size-8 cursor-not-allowed place-items-center border border-slate-200 text-slate-300" aria-disabled="true">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="grid size-8 place-items-center border border-slate-300 bg-white text-lg text-slate-700 transition-colors hover:border-[#173f88] hover:text-[#173f88]" rel="prev" aria-label="Previous page">‹</a>
        @endif

        @foreach($pages as $page)
            @if($previousVisiblePage !== null && $page - $previousVisiblePage > 1)
                <span class="grid size-8 place-items-center text-xs font-bold text-slate-500">…</span>
            @endif
            @if($page === $currentPage)
                <span class="grid size-8 place-items-center bg-[#173f88] text-xs font-bold text-white" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="grid size-8 place-items-center border border-slate-300 bg-white text-xs font-bold text-slate-700 transition-colors hover:border-[#173f88] hover:text-[#173f88]">{{ $page }}</a>
            @endif
            @php($previousVisiblePage = $page)
        @endforeach

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="grid size-8 place-items-center border border-slate-300 bg-white text-lg text-slate-700 transition-colors hover:border-[#173f88] hover:text-[#173f88]" rel="next" aria-label="Next page">›</a>
        @else
            <span class="grid size-8 cursor-not-allowed place-items-center border border-slate-200 text-slate-300" aria-disabled="true">›</span>
        @endif
    </nav>
@endif
