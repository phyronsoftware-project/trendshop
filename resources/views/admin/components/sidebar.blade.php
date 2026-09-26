@php
    $groups = [
        ['label' => 'Catalog', 'icon' => 'catalog', 'active' => request()->routeIs('admin.products.*', 'admin.categories.*'), 'items' => [
            ['Products', 'admin.products.index', 'product', 'admin.products.*'],
            ['Categories', 'admin.categories.index', 'category', 'admin.categories.*'],
        ]],
        ['label' => 'Sales', 'icon' => 'sales', 'active' => request()->routeIs('admin.orders.*'), 'items' => [
            ['Orders', 'admin.orders.index', 'order', 'admin.orders.*'],
        ]],
        ['label' => 'Users', 'icon' => 'customers', 'active' => request()->routeIs('admin.customers.*'), 'items' => [
            ['Manage users', 'admin.customers.index', 'customer', 'admin.customers.*'],
        ]],
        ['label' => 'Support', 'icon' => 'message', 'active' => request()->routeIs('admin.chat.*'), 'items' => [
            ['Messages', 'admin.chat.index', 'message', 'admin.chat.*'],
        ]],
        ['label' => 'Content', 'icon' => 'content', 'active' => request()->routeIs('admin.content.*'), 'items' => [
            ['Website pages', 'admin.content.index', 'page', 'admin.content.*'],
        ]],
        ['label' => 'System Settings', 'icon' => 'settings', 'active' => request()->routeIs('admin.settings.*'), 'items' => [
            ['General & delivery', 'admin.settings.index', 'delivery', 'admin.settings.*'],
        ]],
    ];
@endphp

{{-- Match main and submenu rows with consistent icons, widths and heights. --}}
<aside data-admin-sidebar class="fixed bottom-0 left-0 top-12 z-40 w-[236px] -translate-x-full overflow-y-auto bg-linear-to-b from-[#173f88] to-[#0a2f6b] text-blue-50 shadow-xl transition-[width,transform] duration-300 lg:translate-x-0">
    <nav class="py-2">
        <button type="button" data-sidebar-close class="ml-auto mr-2 grid size-8 place-items-center text-xl lg:hidden" aria-label="Close navigation">×</button>
        <a data-admin-load href="{{ route('admin.dashboard') }}" @class(['flex h-11 w-full items-center gap-3 border-l-3 px-4 text-xs font-semibold transition-colors', 'border-white bg-white/15 text-white' => request()->routeIs('admin.dashboard'), 'border-transparent hover:bg-white/10' => !request()->routeIs('admin.dashboard')])>
            <span class="grid size-5 shrink-0 place-items-center"><x-admin.icon name="home" class="size-[17px]" /></span>
            <span data-sidebar-label>Dashboard</span>
        </a>

        @foreach($groups as $group)
            <div data-sidebar-group>
                <button type="button" data-sidebar-group-toggle class="flex h-11 w-full items-center gap-3 border-l-3 px-4 text-left text-xs font-semibold transition-colors {{ $group['active'] ? 'border-white bg-white/15 text-white' : 'border-transparent hover:bg-white/10' }}" aria-expanded="{{ $group['active'] ? 'true' : 'false' }}">
                    <span class="grid size-5 shrink-0 place-items-center"><x-admin.icon :name="$group['icon']" class="size-[17px]" /></span>
                    <span data-sidebar-label class="flex-1 whitespace-nowrap">{{ $group['label'] }}</span>
                    <x-admin.icon name="chevron-down" data-sidebar-chevron class="size-3.5 transition-transform duration-300 {{ $group['active'] ? 'rotate-180' : '' }}" />
                </button>

                <div data-sidebar-group-panel class="grid transition-[grid-template-rows] duration-300 {{ $group['active'] ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]' }}">
                    <div class="overflow-hidden bg-black/15">
                        @foreach($group['items'] as [$label, $route, $icon, $activePattern])
                            <a data-admin-load href="{{ route($route) }}" class="flex h-11 w-full items-center gap-3 border-l-3 px-4 text-[11px] transition-colors {{ request()->routeIs($activePattern) ? 'border-blue-300 bg-black/20 text-white' : 'border-transparent text-blue-100 hover:bg-white/5 hover:text-white' }}">
                                <span class="grid size-5 shrink-0 place-items-center"><x-admin.icon :name="$icon" class="size-4" /></span>
                                <span data-sidebar-label class="truncate">{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <a data-admin-load href="{{ route('admin.notifications.index') }}" class="flex h-11 w-full items-center gap-3 border-l-3 px-4 text-xs font-semibold transition-colors {{ request()->routeIs('admin.notifications.*') ? 'border-white bg-white/15 text-white' : 'border-transparent hover:bg-white/10' }}">
            <span class="relative grid size-5 shrink-0 place-items-center"><x-admin.icon name="bell" class="size-[17px]" />@if(($adminUnreadNotifications ?? 0) > 0)<span class="absolute -right-1 -top-1 size-2 rounded-full bg-red-400"></span>@endif</span>
            <span data-sidebar-label>Notifications</span>
        </a>
    </nav>

    <button type="button" data-sidebar-collapse class="absolute bottom-0 hidden h-11 w-full items-center justify-end border-t border-white/10 px-5 hover:bg-white/10 lg:flex" title="Collapse sidebar" aria-label="Collapse sidebar"><x-admin.icon name="chevron-left" data-sidebar-collapse-icon class="size-5 transition-transform duration-300" /></button>
</aside>
