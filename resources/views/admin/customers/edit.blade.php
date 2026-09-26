@extends('admin.layouts.app')
@section('title', 'Edit user')
@section('breadcrumb', 'Users / Edit')
@section('content')
<div class="mb-5"><h1 class="text-xl font-bold">Edit user</h1><p class="mt-1 text-xs text-slate-500">Update account details, role and access.</p></div>

{{-- Keep the account image visible and allow administrator avatars to be replaced. --}}
<div class="grid items-start gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="border border-slate-200 bg-white p-4 shadow-sm">
        <img data-admin-profile-preview src="{{ $customer->avatarUrl() }}" alt="{{ $customer->name }}" class="aspect-square w-full border border-slate-200 bg-slate-50 object-cover">
        @if($customer->role === 'admin')
            <label class="mt-3 flex h-9 cursor-pointer items-center justify-center gap-2 border border-[#173f88] bg-white px-3 text-xs font-bold text-[#173f88] transition-colors hover:bg-blue-50">
                <x-admin.icon name="image" class="size-4" />
                Upload admin image
                <input data-admin-profile-input form="admin-user-edit-form" type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
            <p class="mt-1.5 text-center text-[10px] text-slate-400">JPG, PNG or WebP · Max 3MB</p>
        @endif
        <div class="mt-4 text-center">
            <h2 class="break-words text-sm font-bold text-slate-900">{{ $customer->name }}</h2>
            <p class="mt-1 break-all text-[11px] text-slate-500">{{ $customer->email }}</p>
            <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                @forelse($customer->socialAccounts->pluck('provider')->unique() as $provider)
                    <span @class(['inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-bold capitalize', 'bg-red-50 text-red-700' => $provider === 'google', 'bg-sky-50 text-sky-700' => $provider === 'telegram', 'bg-slate-100 text-slate-700' => ! in_array($provider, ['google', 'telegram'], true)])>
                        <img src="{{ asset('Logo-Socail/'.($provider === 'google' ? 'google.png' : ($provider === 'telegram' ? 'telegram.png' : 'communication.png'))) }}" alt="" class="size-3.5 object-contain">
                        {{ $provider }}
                    </span>
                @empty
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">Password account</span>
                @endforelse
            </div>
        </div>
    </aside>

    <form id="admin-user-edit-form" method="POST" action="{{ route('admin.customers.update', $customer) }}" enctype="multipart/form-data" class="border border-slate-200 bg-white p-5 shadow-sm">@csrf @method('PUT') @include('admin.customers._form', ['customer' => $customer, 'submitLabel' => 'Save changes'])</form>
</div>
@endsection
