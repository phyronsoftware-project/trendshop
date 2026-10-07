@extends('layouts.storefront')

@section('title', 'TrendShop — Profile')

@section('content')
<section class="min-h-[70vh] bg-[#f7f9fc] py-10 dark:bg-black">
    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[320px_1fr] lg:px-8">
        {{-- Keep profile identity and navigation together in the left tab box. --}}
        <aside class="h-fit border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-[#0e1113]">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" novalidate data-validate-form class="text-center">@csrf @method('PATCH')
                <input type="hidden" name="name" value="{{ $user->name }}"><input type="hidden" name="email" value="{{ $user->email }}"><input type="hidden" name="phone" value="{{ $user->phone }}">
                <label class="relative mx-auto block h-[205px] w-[200px] cursor-pointer overflow-hidden bg-slate-100 dark:bg-slate-900">
                    {{-- Prefer an uploaded photo, then the customer's Google or Telegram account image. --}}
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-full object-cover">
                    <span class="absolute bottom-3 right-3 grid size-11 place-items-center rounded-full bg-[#173f88] text-white shadow"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4H9.5L8 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3l-1.5-2Z"/><circle cx="12" cy="13" r="3"/></svg></span>
                    <input type="file" name="profile_image" accept="image/*" data-validation="image|max-file:3072" data-submit-on-change class="sr-only">
                </label>
                <x-form-error name="profile_image" />
                <p class="mt-5 break-all text-center text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
            </form>
            <a href="{{ route('products.index') }}" class="mt-5 flex h-11 items-center justify-center border border-[#173f88] text-sm font-bold text-[#173f88] dark:text-white">Continue shopping</a>
            <nav class="mt-6 grid border-t border-slate-200 pt-4 text-sm font-semibold dark:border-slate-700">
                <a href="#account" class="px-3 py-3 hover:bg-blue-50 dark:hover:bg-slate-900">Account information</a>
                <a href="#addresses" class="px-3 py-3 hover:bg-blue-50 dark:hover:bg-slate-900">My addresses</a>
                <a href="#orders" class="px-3 py-3 hover:bg-blue-50 dark:hover:bg-slate-900">Order history</a>
                <a href="#password" class="px-3 py-3 hover:bg-blue-50 dark:hover:bg-slate-900">Change password</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full px-3 py-3 text-left text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20">Sign out</button></form>
            </nav>
        </aside>

        <div class="grid gap-6">
            {{-- Update live customer account information. --}}
            <section id="account" class="border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-[#0e1113] sm:p-8">
                <h1 class="text-2xl font-bold text-slate-950 dark:text-white">Account information</h1>
                <form method="POST" action="{{ route('profile.update') }}" novalidate data-validate-form class="mt-6 grid gap-4 sm:grid-cols-2">@csrf @method('PATCH')
                    <label class="grid gap-2 text-sm font-semibold">Full name<input name="name" value="{{ old('name', $user->name) }}" data-validation="required|max:150" class="h-11 border border-slate-200 bg-slate-50 px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900"><x-form-error name="name" /></label>
                    <label class="grid gap-2 text-sm font-semibold">Email address<input name="email" type="email" value="{{ old('email', $user->email) }}" data-validation="required|email|max:255" class="h-11 border border-slate-200 bg-slate-50 px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900"><x-form-error name="email" /></label>
                    <label class="grid gap-2 text-sm font-semibold">Phone number<input name="phone" value="{{ old('phone', $user->phone) }}" data-validation="max:30" class="h-11 border border-slate-200 bg-slate-50 px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900"><x-form-error name="phone" /></label>
                    <div class="flex items-end justify-end"><button class="h-11 bg-[#173f88] px-6 text-sm font-bold text-white">Save changes</button></div>
                </form>
            </section>

            {{-- Manage reusable delivery addresses used at checkout. --}}
            <section id="addresses" class="border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-[#0e1113] sm:p-8">
                <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-2xl font-bold">My addresses</h2><p class="mt-1 text-sm text-slate-500">Select a row to review or update its delivery details.</p></div><span class="text-xs font-bold text-[#173f88]">{{ $user->addresses->count() }} saved</span></div>

                {{-- Display saved addresses as full-width editable rows. --}}
                <div class="mt-5 overflow-hidden border border-slate-200 dark:border-slate-700">
                    @forelse($user->addresses as $address)
                        <details id="address-{{ $address->id }}" class="group border-b border-slate-200 last:border-b-0 dark:border-slate-700">
                            <summary class="flex min-h-20 cursor-pointer list-none items-center gap-4 px-4 py-3 transition-colors hover:bg-blue-50 dark:hover:bg-slate-900">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-blue-50 text-[#173f88] dark:bg-blue-950/40 dark:text-blue-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                                <span class="grid min-w-0 flex-1 gap-1 sm:grid-cols-[160px_180px_1fr] sm:items-center sm:gap-4">
                                    <span class="flex items-center gap-2 font-bold text-slate-900 dark:text-white">{{ $address->label }}@if($address->is_default)<span class="bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Default</span>@endif</span>
                                    <span class="truncate text-sm text-slate-600 dark:text-slate-300">{{ $address->recipient_name }} · {{ $address->phone }}</span>
                                    <span class="truncate text-sm text-slate-500">{{ $address->address_line_1 }}, {{ $address->district }}, {{ $address->city_province }}</span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2 text-xs font-bold text-[#173f88]">Edit<svg class="size-4 transition-transform duration-300 group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5"/></svg></span>
                            </summary>

                            <div class="border-t border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50 sm:p-5">
                                <form method="POST" action="{{ route('addresses.update', $address) }}" novalidate data-validate-form class="grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
                                    @foreach([['label','Label','required|max:100'],['recipient_name','Recipient name','required|max:150'],['phone','Phone','required|max:30'],['address_line_1','Street address','required|max:255'],['address_line_2','Street address 2','max:255'],['commune','Commune','max:150'],['district','District','max:150'],['city_province','City / Province','required'],['postal_code','Postal code','max:20']] as [$name,$label,$validation])
                                        <label class="grid gap-1 text-xs font-semibold">{{ $label }}@if($name === 'city_province')<select name="city_province" data-validation="{{ $validation }}" class="h-10 border border-slate-200 bg-white px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900">@foreach($provinces as $province)<option value="{{ $province }}" @selected(old('city_province', $address->city_province) === $province)>{{ $province }}</option>@endforeach</select>@else<input name="{{ $name }}" value="{{ old($name, $address->{$name}) }}" data-validation="{{ $validation }}" class="h-10 border border-slate-200 bg-white px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900">@endif<x-form-error :name="$name" /></label>
                                    @endforeach
                                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_default" value="1" @checked($address->is_default)> Set as default</label>
                                    <div class="flex justify-end"><button class="h-10 bg-[#173f88] px-5 text-sm font-bold text-white">Update address</button></div>
                                </form>
                                <form method="POST" action="{{ route('addresses.destroy', $address) }}" class="mt-3 flex justify-end">@csrf @method('DELETE')<button class="h-9 border border-red-200 px-4 text-xs font-bold text-red-500 transition-colors hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/20">Remove address</button></form>
                            </div>
                        </details>
                    @empty
                        <p class="p-8 text-center text-sm text-slate-500">No delivery address yet.</p>
                    @endforelse
                </div>

                <details class="group mt-5 border border-slate-200 dark:border-slate-700">
                    <summary class="flex h-12 cursor-pointer list-none items-center justify-between px-4 text-sm font-bold text-[#173f88] hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-slate-900"><span>＋ Add new address</span><svg class="size-4 transition-transform duration-300 group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5"/></svg></summary>
                    <form method="POST" action="{{ route('addresses.store') }}" novalidate data-validate-form class="grid gap-3 border-t border-slate-200 p-4 dark:border-slate-700 sm:grid-cols-2">@csrf
                        @foreach([['label','Label','required|max:100'],['recipient_name','Recipient name','required|max:150'],['phone','Phone','required|max:30'],['address_line_1','Street address','required|max:255'],['address_line_2','Street address 2','max:255'],['commune','Commune','max:150'],['district','District','max:150'],['city_province','City / Province','required'],['postal_code','Postal code','max:20']] as [$name,$label,$validation])
                            <label class="grid gap-1 text-xs font-semibold">{{ $label }}@if($name === 'city_province')<select name="city_province" data-validation="{{ $validation }}" class="h-10 border border-slate-200 px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900"><option value="" disabled @selected(! old('city_province'))>Select province</option>@foreach($provinces as $province)<option value="{{ $province }}" @selected(old('city_province') === $province)>{{ $province }}</option>@endforeach</select>@else<input name="{{ $name }}" value="{{ old($name) }}" data-validation="{{ $validation }}" class="h-10 border border-slate-200 px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900">@endif<x-form-error :name="$name" /></label>
                        @endforeach
                        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_default" value="1"> Set as default</label><button class="h-10 bg-[#173f88] px-5 text-sm font-bold text-white">Add address</button>
                    </form>
                </details>
            </section>

            {{-- Display immutable order snapshots from the database. --}}
            {{-- Display order history timestamps in Cambodia local time. --}}
            <section id="orders" class="border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-[#0e1113] sm:p-8"><h2 class="text-2xl font-bold">Order history</h2><div class="mt-5 grid gap-5">@forelse($orderGroups as $dateLabel => $group)<div><div class="mb-2 flex items-center gap-3"><h3 class="text-sm font-bold">{{ $dateLabel }}</h3><span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span></div><div class="grid gap-2">@foreach($group as $order)<article class="border border-slate-200 p-4 transition-colors hover:border-[#173f88] dark:border-slate-700"><div class="flex flex-wrap justify-between gap-2"><a href="{{ route('orders.show', $order) }}" class="font-bold text-[#173f88] dark:text-blue-300">{{ $order->order_number }}</a><span class="text-sm font-bold">{{ ucfirst($order->status) }} · ${{ $order->grand_total }}</span></div><p class="mt-2 text-sm text-slate-500">{{ $order->items->pluck('product_name')->join(', ') }}</p><div class="mt-2 flex items-center justify-between gap-3"><time class="text-xs text-slate-400">{{ ($order->placed_at ?? $order->created_at)->timezone((string) config('app.display_timezone'))->format('h:i A') }}</time><a href="{{ route('orders.show', $order) }}" class="text-xs font-bold text-[#173f88] dark:text-blue-300">View details →</a></div></article>@endforeach</div></div>@empty<p class="text-sm text-slate-500">No orders yet.</p>@endforelse</div></section>

            {{-- Require the current password before updating credentials. --}}
            <section id="password" class="border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-[#0e1113] sm:p-8"><h2 class="text-2xl font-bold">Change password</h2><form method="POST" action="{{ route('profile.password') }}" novalidate data-validate-form class="mt-5 grid items-start gap-3 sm:grid-cols-3">@csrf @method('PUT')<div class="grid gap-1"><input type="password" name="current_password" data-validation="required" placeholder="Current password" class="h-11 border border-slate-200 px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900"><x-form-error name="current_password" /></div><div class="grid gap-1"><input type="password" name="password" data-validation="required|min:8" placeholder="New password" class="h-11 border border-slate-200 px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900"><x-form-error name="password" /></div><div class="grid gap-1"><input type="password" name="password_confirmation" data-validation="required|same:password" placeholder="Confirm password" class="h-11 border border-slate-200 px-3 outline-none transition-colors focus:border-[#173f88] dark:border-slate-700 dark:bg-slate-900"><x-form-error name="password_confirmation" /></div><button class="h-11 bg-[#173f88] px-6 text-sm font-bold text-white sm:col-start-3">Update password</button></form></section>
        </div>
    </div>
</section>
@endsection
