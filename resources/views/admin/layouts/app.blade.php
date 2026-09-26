<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — TrendShop</title>
    @fonts
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin-shell min-h-screen bg-[#f4f6f9] text-sm text-slate-800 antialiased">
    @include('admin.components.header')
    @include('admin.components.sidebar')
    <div data-admin-backdrop class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>
    <main data-admin-main class="min-h-screen pt-12 transition-[padding] duration-300 lg:pl-[236px]">
        <div class="border-b border-slate-200 bg-white px-5 py-3 text-xs text-slate-500">Dashboard / @yield('breadcrumb', 'Overview')</div>
        <div class="p-4 sm:p-6">
            {{-- Color admin feedback by status and let JavaScript dismiss it after three seconds. --}}
            @php
                $adminAlertMessage = session('success') ?? session('warning') ?? session('error') ?? ($errors->any() ? $errors->first() : null);
                $adminAlertStatus = session('success') ? 'success' : (session('warning') ? 'warning' : 'error');
            @endphp
            @if($adminAlertMessage)<div data-admin-alert @class(['mb-4 flex items-center gap-3 border-l-4 px-4 py-3 text-xs font-semibold shadow-sm transition-all duration-300','border-emerald-500 bg-emerald-50 text-emerald-800'=>$adminAlertStatus==='success','border-amber-500 bg-amber-50 text-amber-800'=>$adminAlertStatus==='warning','border-red-500 bg-red-50 text-red-700'=>$adminAlertStatus==='error'])><span class="flex-1">{{ $adminAlertMessage }}</span><button type="button" data-admin-alert-close class="grid size-6 place-items-center text-base opacity-70 hover:opacity-100" aria-label="Close alert">×</button></div>@endif
            @yield('content')
        </div>
    </main>
    {{-- Cover the admin body while server-backed data is loading. --}}
    <div data-admin-loader class="fixed inset-0 z-[100] hidden items-center justify-center bg-white/75 backdrop-blur-sm"><div class="grid justify-items-center gap-3"><span class="size-9 animate-spin rounded-full border-4 border-slate-200 border-t-[#173f88]"></span><span class="text-xs font-bold text-[#173f88]">Loading data...</span></div></div>
</body>
</html>
