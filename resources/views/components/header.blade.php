{{-- Provide the shared responsive customer navigation. --}}
<header data-storefront-header class="sticky top-0 z-50 border-b border-slate-200/50 bg-white/55 shadow-sm shadow-slate-900/5 transition-transform duration-300 ease-out will-change-transform backdrop-blur-xl dark:border-slate-700/50 dark:bg-black/55 dark:shadow-black/20">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('products.index') }}" class="group flex items-center gap-2.5" aria-label="TrendShop">
            <img src="{{ asset('logo_web/image.png') }}" alt="TrendShop logo" class="size-10 object-contain transition-transform duration-300 group-hover:-rotate-3 group-hover:scale-105">
            <span class="text-[22px] font-bold tracking-tight text-slate-950 dark:text-white">Trend<span class="text-[#1f62c9]">Shop</span></span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Primary navigation">
            <a href="{{ route('products.index') }}" @class(['rounded-lg px-3.5 py-2 text-[13px] font-semibold transition-colors', 'border border-slate-200/60 bg-white/55 text-[#173f88] shadow-sm backdrop-blur-xl dark:border-slate-700/50 dark:bg-black/45 dark:text-white' => request()->routeIs('products.index'), 'text-slate-600 hover:bg-slate-100 hover:text-[#173f88] dark:text-slate-300 dark:hover:bg-[#0e1113] dark:hover:text-white' => ! request()->routeIs('products.index')]) data-i18n="nav.products">Products</a>
            <a href="{{ route('about') }}" @class(['rounded-lg px-3.5 py-2 text-[13px] font-semibold transition-colors', 'border border-slate-200/60 bg-white/55 text-[#173f88] shadow-sm backdrop-blur-xl dark:border-slate-700/50 dark:bg-black/45 dark:text-white' => request()->routeIs('about'), 'text-slate-600 hover:bg-slate-100 hover:text-[#173f88] dark:text-slate-300 dark:hover:bg-[#0e1113] dark:hover:text-white' => ! request()->routeIs('about')]) data-i18n="nav.about">About us</a>
            <a href="{{ route('privacy') }}" @class(['rounded-lg px-3.5 py-2 text-[13px] font-semibold transition-colors', 'border border-slate-200/60 bg-white/55 text-[#173f88] shadow-sm backdrop-blur-xl dark:border-slate-700/50 dark:bg-black/45 dark:text-white' => request()->routeIs('privacy'), 'text-slate-600 hover:bg-slate-100 hover:text-[#173f88] dark:text-slate-300 dark:hover:bg-[#0e1113] dark:hover:text-white' => ! request()->routeIs('privacy')]) data-i18n="nav.privacy">Privacy</a>
            <a href="{{ route('chat.index') }}" class="rounded-lg px-3.5 py-2 text-[13px] font-semibold text-slate-600 transition-colors hover:bg-slate-100 hover:text-[#173f88] dark:text-slate-300 dark:hover:bg-[#0e1113] dark:hover:text-white" data-i18n="nav.contact">Contact</a>
        </nav>

        <div class="flex items-center gap-1.5">
            {{-- Select Khmer, English or Chinese with real flag assets. --}}
            <div class="relative" data-locale-dropdown>
                <button type="button" data-locale-menu-toggle data-i18n-aria-label="actions.language" aria-expanded="false" class="flex h-10 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-[13px] font-bold text-slate-700 transition-all duration-300 hover:border-[#173f88] hover:text-[#173f88] dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-500">
                    <img data-current-locale-flag data-km-src="{{ asset('flag/khmer.png') }}" data-en-src="{{ asset('flag/us.png') }}" data-zh-src="{{ asset('flag/china.png') }}" src="{{ asset('flag/khmer.png') }}" alt="Cambodia flag" class="size-6 rounded-full object-cover shadow-sm">
                    <span data-current-locale-name class="hidden sm:inline">ខ្មែរ</span>
                    <svg class="size-3.5 transition-transform duration-300" data-locale-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <div data-locale-menu class="invisible absolute right-0 top-[calc(100%+.5rem)] w-40 origin-top-right scale-95 rounded-lg border border-slate-200 bg-white p-1 opacity-0 shadow-2xl transition-all duration-300 dark:border-slate-700 dark:bg-[#0e1113]">
                    @foreach ([
                        ['km', 'khmer.png', 'ខ្មែរ'],
                        ['en', 'us.png', 'English'],
                        ['zh', 'china.png', '中文'],
                    ] as [$locale, $flag, $name])
                        <form method="POST" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" data-locale-option="{{ $locale }}" class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-xs font-semibold transition-colors duration-300 hover:bg-blue-50 hover:text-[#173f88] dark:hover:bg-slate-800 dark:hover:text-white">
                            <img src="{{ asset('flag/'.$flag) }}" alt="{{ $name }}" class="size-5 rounded-full object-cover">
                            <span>{{ $name }}</span>
                        </button></form>
                    @endforeach
                </div>
            </div>
            <button type="button" data-theme-toggle data-i18n-aria-label="actions.theme" class="grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-700 transition-transform duration-150 active:scale-90 dark:border-slate-700 dark:text-slate-200">
                <svg class="size-[18px] dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42m0-12.72-1.42 1.42M7.06 16.94l-1.42 1.42"/><circle cx="12" cy="12" r="4"/></svg>
                <svg class="hidden size-[18px] dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg>
            </button>
            @auth
                {{-- Make the wishlist and cart actions slightly larger and easier to identify. --}}
                <a href="{{ route('wishlist.index') }}" class="relative hidden size-10 place-items-center rounded-lg border border-slate-200 text-slate-700 transition-all duration-200 hover:border-[#173f88] hover:bg-blue-50 hover:text-[#173f88] active:scale-90 sm:grid dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-500 dark:hover:bg-blue-950/30 dark:hover:text-blue-400" aria-label="Wishlist"><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg><span data-wishlist-count class="absolute -right-1 -top-1 grid min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[9px] font-bold leading-4 text-white {{ $wishlistCount > 0 ? '' : 'hidden' }}">{{ $wishlistCount }}</span></a>
                <a href="{{ route('cart.index') }}" class="relative hidden size-10 place-items-center rounded-lg border border-slate-200 text-slate-700 transition-all duration-200 hover:border-[#173f88] hover:bg-blue-50 hover:text-[#173f88] active:scale-90 sm:grid dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-500 dark:hover:bg-blue-950/30 dark:hover:text-blue-400" aria-label="Cart"><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2 11h10l2-7H6"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg><span data-cart-count class="absolute -right-1 -top-1 grid min-w-4 place-items-center rounded-full bg-[#173f88] px-1 text-[9px] font-bold leading-4 text-white {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span></a>
                {{-- Show the signed-in name and keep account actions inside one dropdown. --}}
                <div data-account-dropdown class="relative hidden sm:block">
                    <button type="button" data-account-menu-toggle aria-expanded="false" class="group flex h-10 max-w-[230px] items-center gap-2 px-1.5 text-[13px] font-semibold text-slate-700 transition-colors hover:text-[#173f88] dark:text-slate-200 dark:hover:text-blue-400">
                        {{-- Keep the account trigger light while giving the profile icon a clear visual anchor. --}}
                        <span class="grid size-9 shrink-0 place-items-center rounded-full border border-slate-200 text-[#173f88] transition-colors group-hover:border-[#173f88] dark:border-slate-700 dark:text-blue-400 dark:group-hover:border-blue-400">
                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6"/></svg>
                        </span>
                        <span class="truncate">{{ auth()->user()->name ?: auth()->user()->email }}</span>
                        <svg data-account-chevron class="size-3.5 shrink-0 text-slate-400 transition-transform duration-200 group-hover:text-[#173f88] dark:group-hover:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                    </button>
                    <div data-account-menu class="invisible absolute right-0 top-[calc(100%+.5rem)] w-48 origin-top-right scale-95 rounded-lg border border-slate-200 bg-white p-1 opacity-0 shadow-2xl transition-all duration-200 dark:border-slate-700 dark:bg-[#0e1113]">
                        <a href="{{ route('profile') }}" class="flex h-9 items-center gap-2 rounded-md px-2.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-blue-50 hover:text-[#173f88] dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-white"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6"/></svg><span>My profile</span></a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 pt-1 dark:border-slate-800">@csrf<button type="submit" class="flex h-9 w-full items-center gap-2 rounded-md px-2.5 text-left text-xs font-semibold text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-950/20"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m10 17 5-5-5-5M15 12H3M15 3h5a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-5"/></svg><span>Logout</span></button></form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="hidden h-10 items-center gap-1.5 rounded-lg bg-[#173f88] px-3.5 text-[13px] font-bold text-white shadow-lg shadow-blue-900/15 transition-colors hover:bg-[#0a2f6b] sm:flex"><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6"/></svg><span>Sign in</span></a>
            @endauth
            <button type="button" data-menu-toggle data-i18n-aria-label="actions.menu" aria-expanded="false" class="grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-700 lg:hidden dark:border-slate-700 dark:text-white">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    {{-- Keep mobile navigation hidden until explicitly opened. --}}
    <nav data-mobile-menu class="hidden border-t border-slate-200 bg-white px-4 py-3 lg:hidden dark:border-slate-800 dark:bg-[#0e1113]" aria-label="Mobile navigation">
        <div class="mx-auto grid max-w-7xl gap-1.5 text-sm">
            <a href="{{ route('products.index') }}" class="rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" data-i18n="nav.products">Products</a>
            <a href="{{ route('about') }}" class="rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" data-i18n="nav.about">About us</a>
            <a href="{{ route('privacy') }}" class="rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" data-i18n="nav.privacy">Privacy</a>
            <a href="{{ route('chat.index') }}" class="rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" data-i18n="nav.contact">Contact</a>
            @auth
                <a href="{{ route('cart.index') }}" class="rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-900">Cart</a><a href="{{ route('wishlist.index') }}" class="rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-900">Wishlist</a>
                {{-- Keep the customer name visible in the mobile account action too. --}}
                <a href="{{ route('profile') }}" class="flex items-center gap-2 rounded-lg bg-[#173f88] px-3 py-2.5 font-semibold text-white"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6"/></svg><span class="truncate">{{ auth()->user()->name ?: auth()->user()->email }}</span></a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m10 17 5-5-5-5M15 12H3M15 3h5a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-5"/></svg>Logout</button></form>
            @else
                <a href="{{ route('login') }}" class="rounded-lg bg-[#173f88] px-3 py-2.5 text-center font-semibold text-white">Sign in</a>
            @endauth
        </div>
    </nav>
</header>
