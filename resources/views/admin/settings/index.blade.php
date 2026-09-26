@extends('admin.layouts.app')

@section('title', 'System settings')
@section('breadcrumb', 'System Settings')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-bold">System settings</h1>
        <p class="mt-1 text-xs text-slate-500">Control delivery, contact details and storefront social links.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="grid gap-5">
        @csrf
        @method('PUT')

        {{-- Configure the two delivery zones and public contact information. --}}
        <section class="border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold">General & delivery</h2>
            <p class="mt-1 text-[11px] text-slate-500">Phnom Penh City uses the city rate. Every other Cambodian province uses one province rate.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1 text-[11px] font-bold">Phnom Penh City fee (USD)<input type="number" min="0" max="9999" step="0.01" name="settings[city_delivery_fee]" value="{{ old('settings.city_delivery_fee', $cityDeliveryFee) }}" class="h-9 border border-slate-200 px-3 text-xs"></label>
                <label class="grid gap-1 text-[11px] font-bold">All provinces fee (USD)<input type="number" min="0" max="9999" step="0.01" name="settings[province_delivery_fee]" value="{{ old('settings.province_delivery_fee', $provinceDeliveryFee) }}" class="h-9 border border-slate-200 px-3 text-xs"></label>
                <label class="grid gap-1 text-[11px] font-bold">Free delivery minimum<input type="number" min="0" step="0.01" name="settings[free_delivery_minimum]" value="{{ old('settings.free_delivery_minimum', $settings['free_delivery_minimum'] ?? 100) }}" class="h-9 border border-slate-200 px-3 text-xs"></label>
                <label class="grid gap-1 text-[11px] font-bold">Support email<input type="email" name="settings[support_email]" value="{{ old('settings.support_email', $settings['support_email'] ?? '') }}" class="h-9 border border-slate-200 px-3 text-xs"></label>
                <label class="grid gap-1 text-[11px] font-bold">Support phone<input name="settings[support_phone]" value="{{ old('settings.support_phone', $settings['support_phone'] ?? '') }}" class="h-9 border border-slate-200 px-3 text-xs"></label>
            </div>
        </section>

        <section class="border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold">Social media links</h2>
            <div class="mt-4 grid gap-3">
                @foreach($socialLinks as $link)
                    <div class="grid items-end gap-3 sm:grid-cols-[130px_1fr_90px]"><label class="grid gap-1 text-[11px] font-bold">Platform<input value="{{ $link->label }}" disabled class="h-9 border border-slate-200 bg-slate-50 px-3 text-xs"></label><label class="grid gap-1 text-[11px] font-bold">URL<input type="url" name="social_links[{{ $link->id }}][url]" value="{{ $link->url }}" required class="h-9 border border-slate-200 px-3 text-xs"></label><label class="flex h-9 items-center gap-2 text-[11px] font-bold"><input type="checkbox" name="social_links[{{ $link->id }}][is_active]" value="1" @checked($link->is_active)> Active</label></div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end"><button class="h-9 bg-[#173f88] px-6 text-xs font-bold text-white">Save settings</button></div>
    </form>
@endsection
