<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }
    }

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $email = 'new-user-'.uniqid().'@trendshop.test';

        $this->actingAs($admin, 'admin')->post(route('admin.customers.store'), [
            'name' => 'New Customer',
            'email' => $email,
            'phone' => '099'.random_int(100000, 999999),
            'role' => 'customer',
            'locale' => 'en',
            'status' => 'active',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertRedirect(route('admin.customers.index'));

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertTrue(Hash::check('SecurePassword123!', $user->password));
    }

    public function test_admin_can_update_a_user_without_replacing_the_password(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::factory()->create(['role' => 'customer', 'password' => 'OriginalPassword123!']);
        $password = $customer->password;

        $this->actingAs($admin, 'admin')->put(route('admin.customers.update', $customer), [
            'name' => 'Updated Customer',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'role' => 'customer',
            'locale' => 'km',
            'status' => 'blocked',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.customers.index'));

        $customer->refresh();
        $this->assertSame('Updated Customer', $customer->name);
        $this->assertSame('blocked', $customer->status);
        $this->assertSame($password, $customer->password);
    }

    public function test_admin_can_upload_an_image_for_an_administrator_account(): void
    {
        Storage::fake('public');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $administrator = User::factory()->create(['role' => 'admin', 'locale' => 'en', 'status' => 'active']);

        $this->actingAs($admin, 'admin')->put(route('admin.customers.update', $administrator), [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'phone' => $administrator->phone,
            'role' => 'admin',
            'locale' => $administrator->locale,
            'status' => $administrator->status,
            'password' => '',
            'password_confirmation' => '',
            'profile_image' => UploadedFile::fake()->image('administrator.jpg'),
        ])->assertRedirect(route('admin.customers.index'));

        $administrator->refresh();
        $this->assertNotNull($administrator->profile_image_path);
        Storage::disk('public')->assertExists($administrator->profile_image_path);
    }

    public function test_admin_cannot_upload_an_image_for_a_customer_account(): void
    {
        Storage::fake('public');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin, 'admin')->from(route('admin.customers.edit', $customer))->put(route('admin.customers.update', $customer), [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'role' => 'customer',
            'locale' => $customer->locale,
            'status' => $customer->status,
            'password' => '',
            'password_confirmation' => '',
            'profile_image' => UploadedFile::fake()->image('customer.jpg'),
        ])->assertRedirect(route('admin.customers.edit', $customer))->assertSessionHasErrors('profile_image');

        $this->assertNull($customer->fresh()->profile_image_path);
    }

    public function test_admin_can_soft_delete_another_user(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.customers.destroy', $customer))
            ->assertRedirect(route('admin.customers.index'));

        $this->assertSoftDeleted($customer);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.customers.index'))
            ->delete(route('admin.customers.destroy', $admin))
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('warning');

        $this->assertNotSoftDeleted($admin);
    }

    public function test_customer_list_shows_linked_social_login_providers(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::factory()->create(['role' => 'customer']);
        $avatarUrl = 'https://example.test/telegram-customer-avatar.jpg';
        UserSocialAccount::query()->create([
            'user_id' => $customer->id,
            'provider' => 'telegram',
            'provider_user_id' => 'telegram-'.uniqid(),
            'provider_avatar_url' => $avatarUrl,
            'metadata' => [],
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.customers.index', ['search' => $customer->email]))
            ->assertOk()
            ->assertSee('telegram')
            ->assertSee($avatarUrl, false);
    }

    public function test_customer_list_paginates_eight_users_per_page(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $searchMarker = 'pagination-'.uniqid();

        // Create nine matching users so the ninth row must move to page two.
        User::factory()->count(9)->create([
            'name' => $searchMarker,
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.customers.index', ['search' => $searchMarker]))
            ->assertOk()
            ->assertSee('Showing 1–8 of 9 users')
            ->assertViewHas('customers', fn ($customers): bool => $customers->count() === 8
                && $customers->perPage() === 8
                && $customers->lastPage() === 2);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.customers.index', ['search' => $searchMarker, 'page' => 2]))
            ->assertOk()
            ->assertViewHas('customers', fn ($customers): bool => $customers->count() === 1
                && $customers->currentPage() === 2);
    }

    public function test_customer_edit_page_shows_the_social_account_image(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::factory()->create(['role' => 'customer']);
        $avatarUrl = 'https://example.test/google-customer-avatar.jpg';
        UserSocialAccount::query()->create([
            'user_id' => $customer->id,
            'provider' => 'google',
            'provider_user_id' => 'google-edit-'.uniqid(),
            'provider_avatar_url' => $avatarUrl,
            'metadata' => [],
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.customers.edit', $customer))
            ->assertOk()
            ->assertSee($avatarUrl, false)
            ->assertSee('google');
    }

    public function test_admin_can_filter_customers_by_social_login_provider(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $searchMarker = 'provider-filter-'.uniqid();
        $googleCustomer = User::factory()->create(['name' => $searchMarker.' Google', 'role' => 'customer']);
        $telegramCustomer = User::factory()->create(['name' => $searchMarker.' Telegram', 'role' => 'customer']);
        UserSocialAccount::query()->create([
            'user_id' => $googleCustomer->id,
            'provider' => 'google',
            'provider_user_id' => 'google-'.uniqid(),
            'metadata' => [],
        ]);
        UserSocialAccount::query()->create([
            'user_id' => $telegramCustomer->id,
            'provider' => 'telegram',
            'provider_user_id' => 'telegram-'.uniqid(),
            'metadata' => [],
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.customers.index', [
                'search' => $searchMarker,
                'login_method' => 'telegram',
            ]))
            ->assertSee($telegramCustomer->email)
            ->assertDontSee($googleCustomer->email)
            ->assertViewHas('loginMethod', 'telegram');
    }

    public function test_admin_can_filter_customers_without_a_social_login(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $searchMarker = 'password-filter-'.uniqid();
        $passwordCustomer = User::factory()->create(['name' => $searchMarker.' Password', 'role' => 'customer']);
        $telegramCustomer = User::factory()->create(['name' => $searchMarker.' Telegram', 'role' => 'customer']);
        UserSocialAccount::query()->create([
            'user_id' => $telegramCustomer->id,
            'provider' => 'telegram',
            'provider_user_id' => 'telegram-'.uniqid(),
            'metadata' => [],
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.customers.index', [
                'search' => $searchMarker,
                'login_method' => 'password',
            ]))
            ->assertSee($passwordCustomer->email)
            ->assertDontSee($telegramCustomer->email)
            ->assertViewHas('loginMethod', 'password');
    }
}
