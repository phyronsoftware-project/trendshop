<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('meta_description', 'TrendShop customer storefront')">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'TrendShop')</title>

        @fonts

        {{-- Prevent a light theme flash before the application loads. --}}
        <script>
            const storedTheme = localStorage.getItem('trendshop-theme');
            document.documentElement.classList.toggle(
                'dark',
                storedTheme ? storedTheme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches,
            );
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="locale-{{ app()->getLocale() }} min-h-screen bg-[#f7f9fc] text-slate-800 antialiased dark:bg-black dark:text-slate-100">
        <x-header />

        {{-- Render each customer page inside the shared storefront shell. --}}
        <main>
            @yield('content')
        </main>

        <x-footer />
        {{-- Map server feedback to the reusable colored three-second alert. --}}
        @php
            $alertMessage = session('success') ?? session('warning') ?? session('error') ?? ($errors->any() ? $errors->first() : null);
            $alertStatus = session('success') ? 'success' : (session('warning') ? 'warning' : ($alertMessage ? 'error' : 'info'));
        @endphp
        <x-alert :status="$alertStatus" :message="$alertMessage" />
    </body>
</html>
