<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAddressRequest;
use App\Models\UserAddress;
use App\Support\CambodiaProvince;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        // Load the social avatar with the rest of the profile data to avoid a lazy query in the view.
        $user = $request->user()->load([
            'addresses' => fn ($query) => $query->latest(),
            'orders.items',
            'socialAccounts:id,user_id,provider_avatar_url',
        ]);
        $provinces = CambodiaProvince::names();
        $orderGroups = $user->orders->sortByDesc('created_at')->groupBy(function ($order): string {
            // Group profile orders using the customer's Cambodia-local calendar date.
            $placedAt = ($order->placed_at ?? $order->created_at)->timezone((string) config('app.display_timezone'));

            return match (true) {
                $placedAt->isToday() => 'Today',
                $placedAt->isYesterday() => 'Yesterday',
                default => $placedAt->format('l, d M Y'),
            };
        });

        return view('pages.profile', compact('orderGroups', 'provinces', 'user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone,'.$user->id],
            'profile_image' => ['nullable', 'image', 'max:3072'],
        ]);

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }
            $validated['profile_image_path'] = $request->file('profile_image')->store('profiles', 'public');
        }
        unset($validated['profile_image']);
        $user->update($validated);

        return back()->with('success', 'Account information updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function storeAddress(SaveAddressRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->boolean('is_default') || ! $request->user()->addresses()->exists()) {
            $request->user()->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        }
        $request->user()->addresses()->create($validated);

        return redirect()->to(route('profile').'#addresses')->with('success', 'Delivery address added.');
    }

    public function updateAddress(SaveAddressRequest $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $validated = $request->validated();
        $shouldBeDefault = $request->boolean('is_default')
            || $address->is_default
            || ! $request->user()->addresses()->whereKeyNot($address->id)->exists();

        DB::transaction(function () use ($request, $address, $validated, $shouldBeDefault): void {
            if ($shouldBeDefault) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update([...$validated, 'is_default' => $shouldBeDefault]);
        });

        return redirect()->to(route('profile').'#addresses')->with('success', 'Delivery address updated.');
    }

    public function destroyAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();

        return back()->with('success', 'Address removed.');
    }
}
