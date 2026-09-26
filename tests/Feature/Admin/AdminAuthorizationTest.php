<?php

namespace Tests\Feature\Admin;

use App\Models\ContentPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('users')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }
    }

    public function test_guest_is_redirected_to_the_dedicated_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_customer_cannot_access_admin_dashboard_and_keeps_their_storefront_session(): void
    {
        $customer = User::query()->where('role', 'customer')->firstOrFail();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertGuest('admin');
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $recentOrder = Order::query()->latest()->firstOrFail();
        $recentOrder->update(['status' => 'delivered']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertSee('TrendShop sales and catalogue overview')
            ->assertSee(route('admin.products.index'), false)
            ->assertSee(route('admin.customers.index'), false)
            ->assertSee(route('admin.orders.index'), false)
            ->assertSee(route('admin.orders.index', ['payment_status' => 'paid']), false)
            ->assertSee(route('admin.orders.show', $recentOrder), false)
            ->assertSee('bg-emerald-100 text-emerald-800', false)
            ->assertDontSee('MANAGEMENT');
    }

    public function test_admin_and_customer_can_remain_authenticated_in_the_same_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => 'AdminPassword123!',
        ]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'password' => 'CustomerPassword123!',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'AdminPassword123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'CustomerPassword123!',
        ])->assertRedirect(route('profile'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($customer, 'web');
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('profile'))->assertOk();

        // Signing out from the storefront must not end the dashboard session.
        $this->post(route('logout'))->assertRedirect(route('products.index'));
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_management_pages_render_successfully(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $product = Product::query()->firstOrFail();
        $order = Order::query()->firstOrFail();
        $page = ContentPage::query()->firstOrFail();
        $urls = [
            route('admin.products.index'),
            route('admin.products.create'),
            route('admin.products.edit', $product),
            route('admin.categories.index'),
            route('admin.orders.index'),
            route('admin.orders.show', $order),
            route('admin.orders.label', $order),
            route('admin.customers.index'),
            route('admin.customers.create'),
            route('admin.customers.edit', $admin),
            route('admin.content.index'),
            route('admin.content.edit', $page),
            route('admin.settings.index'),
            route('admin.notifications.index'),
            route('admin.chat.index'),
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin, 'admin')->get($url)->assertOk();
        }
    }
}
