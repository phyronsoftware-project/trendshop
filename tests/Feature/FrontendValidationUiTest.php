<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FrontendValidationUiTest extends TestCase
{
    public function test_customer_auth_forms_use_inline_validation_without_native_required_attributes(): void
    {
        // Verify both public account forms expose the shared custom validation contract.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('novalidate', false)
            ->assertSee('data-validate-form', false)
            ->assertSee('data-validation="required|email"', false)
            ->assertSee('data-validation-error="email"', false)
            ->assertDontSee(' required ', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-validation="accepted"', false)
            ->assertSee('data-validation-error="terms"', false)
            ->assertDontSee(' required ', false);
    }

    public function test_customer_profile_forms_use_compact_inline_validation(): void
    {
        if (! Schema::hasTable('users')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        // Render the authenticated page to cover account, address and password forms together.
        $customer = User::query()->where('role', 'customer')->firstOrFail();

        $this->actingAs($customer)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('data-validation="required|max:150"', false)
            ->assertSee('data-validation="required|same:password"', false)
            ->assertSee('data-validation-error="current_password"', false)
            ->assertDontSee(' required ', false);
    }
}
