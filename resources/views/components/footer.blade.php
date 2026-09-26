{{-- Close every customer page with store information and useful links. --}}
<footer id="contact" class="relative overflow-hidden border-t border-blue-900/40 bg-linear-to-br from-[#0a2f6b] via-[#173f88] to-[#0a2f6b] text-blue-50/85">
    <div class="absolute -right-24 -top-24 size-72 rounded-full bg-white/10 blur-3xl"></div>
    <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div class="flex flex-col gap-5">
            <a href="{{ route('products.index') }}" class="flex items-center gap-3 text-white">
                <img src="{{ asset('logo_web/image.png') }}" alt="TrendShop logo" class="size-12 object-contain">
                <span class="text-2xl font-bold">Trend<span class="text-blue-200">Shop</span></span>
            </a>
            <p class="max-w-sm text-sm leading-7 text-blue-50/70" data-i18n="footer.summary">A friendly online store for useful everyday products, designed for simple and confident shopping.</p>
            <div class="flex gap-3">
                @foreach ($socialLinks as $link)
                    @php($icon = ['facebook' => 'communication.png', 'telegram' => 'telegram.png', 'youtube' => 'youtube.png'][$link->platform] ?? 'google.png')
                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="grid size-10 place-items-center rounded-xl border border-white/20 bg-black/15 transition-all duration-300 hover:-translate-y-1 hover:border-white/60 hover:bg-white/10" aria-label="{{ $link->label }}">
                        <img src="{{ asset('Logo-Socail/'.$icon) }}" alt="{{ $link->label }}" class="size-5 object-contain">
                    </a>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="mb-5 font-bold text-white" data-i18n="footer.explore">Explore</h2>
            <div class="flex flex-col gap-3 text-sm">
                <a href="{{ route('products.index') }}" class="transition-colors hover:text-white" data-i18n="nav.products">Products</a>
                <a href="{{ route('about') }}" class="transition-colors hover:text-white" data-i18n="nav.about">About us</a>
                <a href="{{ route('privacy') }}" class="transition-colors hover:text-white" data-i18n="nav.privacy">Privacy</a>
            </div>
        </div>

        <div>
            <h2 class="mb-5 font-bold text-white" data-i18n="footer.support">Support</h2>
            <div class="flex flex-col gap-3 text-sm">
                <a href="{{ route('profile') }}" class="transition-colors hover:text-white" data-i18n="footer.account">My account</a>
                <a href="{{ route('profile') }}#orders" class="transition-colors hover:text-white" data-i18n="footer.orders">Order history</a>
                <a href="{{ route('profile') }}#addresses" class="transition-colors hover:text-white" data-i18n="footer.address">My addresses</a>
            </div>
        </div>

        <div>
            <h2 class="mb-5 font-bold text-white" data-i18n="footer.contact">Contact us</h2>
            <div class="flex flex-col gap-4 text-sm">
                <a href="{{ route('chat.index') }}" class="flex items-center gap-3 font-semibold text-white transition-colors hover:text-blue-200">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/><path d="M8 9h8M8 13h5"/></svg>
                    <span data-i18n="footer.chat">Chat with admin</span>
                </a>
                <a href="mailto:{{ $settings['support_email'] ?? 'support@trendshop.test' }}" class="flex items-center gap-3 transition-colors hover:text-white">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h18v12H3z"/><path d="m3 7 9 6 9-6"/></svg>
                    {{ $settings['support_email'] ?? 'support@trendshop.test' }}
                </a>
                <a href="tel:{{ preg_replace('/\s+/', '', $settings['support_phone'] ?? '+855975786200') }}" class="flex items-center gap-3 transition-colors hover:text-white">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-4-1-1.5 2c-4-1.5-8-5.5-9.5-9.5L8 7 7 3Z"/></svg>
                    {{ $settings['support_phone'] ?? '+855 97 578 6200' }}
                </a>
                <p class="flex items-center gap-3">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <span data-i18n="footer.location">Phnom Penh, Cambodia</span>
                </p>
            </div>
        </div>
    </div>

    <div class="relative border-t border-white/15">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 text-center text-sm text-blue-50/65 sm:flex-row sm:px-6 lg:px-8">
            <p data-i18n="footer.copyright">© 2026 TrendShop. All rights reserved.</p>
        </div>
    </div>
</footer>

{{-- Keep the icon-only back-to-top control fixed to the viewport instead of the footer row. --}}
<button type="button" data-scroll-top data-i18n-aria-label="actions.top" aria-label="Back to top" aria-hidden="true" tabindex="-1" class="pointer-events-none fixed bottom-5 right-4 z-[80] grid size-11 translate-y-3 place-items-center rounded-full border border-white/30 bg-[#173f88] text-white opacity-0 shadow-[0_8px_24px_rgba(15,23,42,0.28)] transition-all duration-300 hover:-translate-y-1 hover:bg-[#0a2f6b] active:scale-95 sm:bottom-6 sm:right-6">
    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg>
</button>
