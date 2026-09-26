@extends('admin.layouts.app')
@section('title', 'Users')
@section('breadcrumb', 'Users / Accounts')
@section('content')
<div class="mb-5 flex items-end justify-between gap-4">
    <div><h1 class="text-xl font-bold">Users</h1><p class="mt-1 text-xs text-slate-500">Create and manage administrator and customer accounts.</p></div>
    <a data-admin-load href="{{ route('admin.customers.create') }}" class="inline-flex h-9 items-center bg-[#173f88] px-4 text-xs font-bold text-white">+ Add user</a>
</div>

{{-- Filter user accounts without losing pagination query parameters. --}}
<form method="GET" class="mb-5 grid gap-2 border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-[1fr_140px_140px_180px_auto]">
    <input name="search" value="{{ request('search') }}" placeholder="name / email / phone" class="h-9 border border-slate-200 px-3 text-xs">
    <select name="role" class="h-9 border border-slate-200 px-3 text-xs"><option value="">All roles</option>@foreach(['admin', 'customer'] as $role)<option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>@endforeach</select>
    <select name="status" class="h-9 border border-slate-200 px-3 text-xs"><option value="">All statuses</option>@foreach(['active', 'inactive', 'blocked'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
    {{-- Filter by the account's authentication provider; Facebook remains unavailable for now. --}}
    <select name="login_method" class="h-9 border border-slate-200 px-3 text-xs">
        <option value="">All login methods</option>
        <option value="google" @selected($loginMethod === 'google')>Google</option>
        <option value="telegram" @selected($loginMethod === 'telegram')>Telegram</option>
        <option value="password" @selected($loginMethod === 'password')>Password</option>
        <option disabled>Facebook (coming later)</option>
    </select>
    <button class="h-9 bg-[#173f88] px-5 text-xs font-bold text-white">Filter</button>
</form>

<section class="overflow-hidden border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1080px] text-left text-xs">
            <thead class="bg-slate-50 text-[10px] uppercase text-slate-500"><tr><th class="px-4 py-3">Image</th><th class="px-4 py-3">User</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Login with</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Orders</th><th class="px-4 py-3">Locale</th><th class="px-4 py-3">Joined</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        {{-- Prefer an uploaded photo, then the customer's Google or Telegram account image. --}}
                        <td class="px-4 py-2"><img src="{{ $customer->avatarUrl() }}" alt="{{ $customer->name }}" class="size-12 border border-slate-200 object-cover"></td>
                        <td class="px-4 py-3"><strong>{{ $customer->name }}</strong><span class="block text-[10px] text-slate-400">#{{ $customer->id }}</span></td>
                        <td class="px-4 py-3"><span class="bg-slate-100 px-2 py-1 text-[10px] font-bold uppercase text-slate-700">{{ $customer->role }}</span></td>
                        {{-- Identify accounts linked through Google or Telegram; otherwise show password login. --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($customer->socialAccounts->pluck('provider')->unique() as $provider)
                                    <span @class(['inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-bold capitalize', 'bg-red-50 text-red-700' => $provider === 'google', 'bg-sky-50 text-sky-700' => $provider === 'telegram', 'bg-slate-100 text-slate-700' => ! in_array($provider, ['google', 'telegram'], true)])>
                                        <img src="{{ asset('Logo-Socail/'.($provider === 'google' ? 'google.png' : ($provider === 'telegram' ? 'telegram.png' : 'communication.png'))) }}" alt="" class="size-3.5 object-contain">
                                        {{ $provider }}
                                    </span>
                                @empty
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">Password</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $customer->email }}<span class="block text-[10px] text-slate-400">{{ $customer->phone ?: 'No phone' }}</span></td>
                        <td class="px-4 py-3 font-bold">{{ $customer->orders_count }}</td>
                        <td class="px-4 py-3 uppercase">{{ $customer->locale }}</td>
                        {{-- Display the customer's local Cambodia join date. --}}
                        <td class="px-4 py-3">{{ $customer->created_at?->timezone((string) config('app.display_timezone'))->format('d M Y') }}</td>
                        <td class="px-4 py-3"><span @class(['px-2 py-1 text-[10px] font-bold uppercase', 'bg-emerald-100 text-emerald-800' => $customer->status === 'active', 'bg-amber-100 text-amber-800' => $customer->status === 'inactive', 'bg-red-100 text-red-700' => $customer->status === 'blocked'])>{{ $customer->status }}</span></td>
                        <td class="px-4 py-3"><div class="flex justify-end gap-2"><a data-admin-load href="{{ route('admin.customers.edit', $customer) }}" class="inline-flex h-8 items-center border border-[#173f88] px-3 text-[10px] font-bold text-[#173f88]">Edit</a>@if(auth('admin')->id() !== $customer->id)<form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')<button class="h-8 bg-red-600 px-3 text-[10px] font-bold text-white">Delete</button></form>@else<span class="inline-flex h-8 items-center px-2 text-[10px] font-semibold text-slate-400">Current account</span>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-10 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- Match the admin pagination footer with a result range and numbered controls. --}}
    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row">
        <p class="text-[11px] font-semibold text-slate-500">Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} users</p>
        <x-pagination :paginator="$customers" />
    </div>
</section>
@endsection
