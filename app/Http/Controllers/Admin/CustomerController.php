<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        // Allow only supported login methods to affect the customer query.
        $loginMethod = $request->string('login_method')->toString();

        if (! in_array($loginMethod, ['google', 'telegram', 'password'], true)) {
            $loginMethod = '';
        }

        // Eager load providers and keep each customer page limited to eight rows.
        $customers = User::query()
            ->with(['socialAccounts:id,user_id,provider,provider_avatar_url'])
            ->withCount('orders')
            ->when($request->string('search')->isNotEmpty(), fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')->orWhere('phone', 'like', '%'.$request->string('search').'%')))
            ->when($request->string('role')->isNotEmpty(), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->when(in_array($loginMethod, ['google', 'telegram'], true), fn ($query) => $query->whereHas('socialAccounts', fn ($socialAccountQuery) => $socialAccountQuery->where('provider', $loginMethod)))
            ->when($loginMethod === 'password', fn ($query) => $query->whereDoesntHave('socialAccounts'))
            ->latest()->paginate(8)->withQueryString();

        return view('admin.customers.index', compact('customers', 'loginMethod'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return redirect()->route('admin.customers.index')->with('success', 'User created successfully.');
    }

    public function edit(User $customer): View
    {
        // Load provider details so the edit page can display the customer's social profile image.
        $customer->load('socialAccounts:id,user_id,provider,provider_avatar_url');

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, User $customer): RedirectResponse
    {
        $data = $request->validated();

        // Preserve the current password when the optional field is left empty.
        if (! $request->filled('password')) {
            unset($data['password']);
        }

        // Replace profile images only for administrator accounts.
        if ($customer->role === 'admin' && $request->string('role')->toString() === 'admin' && $request->hasFile('profile_image')) {
            $previousImagePath = $customer->profile_image_path;
            $data['profile_image_path'] = $request->file('profile_image')->store('profiles', 'public');

            if (filled($previousImagePath) && ! str_starts_with($previousImagePath, 'logo_web/')) {
                Storage::disk('public')->delete($previousImagePath);
            }
        }
        unset($data['profile_image']);

        $customer->update($data);

        return redirect()->route('admin.customers.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $customer): RedirectResponse
    {
        // Keep the active administrator account available for the current session.
        if ($request->user()?->is($customer)) {
            return back()->with('warning', 'You cannot delete your own administrator account.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'User deleted successfully.');
    }
}
