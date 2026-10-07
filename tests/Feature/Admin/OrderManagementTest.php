<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('orders')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }
    }

    public function test_orders_are_grouped_from_newest_delivery_date_to_oldest(): void
    {
        $this->travelTo('2026-09-17 12:00:00');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::query()->where('role', 'customer')->firstOrFail();
        $this->createOrder($customer, 'SORT-TEST-TODAY', now(), now()->subDays(10));
        $this->createOrder($customer, 'SORT-TEST-YESTERDAY', now()->subDay(), now());
        $oldOrder = $this->createOrder($customer, 'SORT-TEST-OLDER', now()->subDays(5), now()->subDay());
        $oldDateLabel = $oldOrder->placed_at->format('l, d M Y');

        $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.index', ['search' => 'SORT-TEST-']));

        $response->assertSeeInOrder([
            'Today',
            'SORT-TEST-TODAY',
            'Yesterday',
            'SORT-TEST-YESTERDAY',
            $oldDateLabel,
            'SORT-TEST-OLDER',
        ]);
    }

    public function test_order_index_paginates_three_complete_date_groups_per_page(): void
    {
        $this->travelTo('2026-09-17 12:00:00');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::query()->where('role', 'customer')->firstOrFail();

        foreach (range(0, 3) as $daysAgo) {
            $this->createOrder(
                $customer,
                "DATE-PAGE-{$daysAgo}",
                now()->subDays($daysAgo),
                now()->subDays($daysAgo),
            );
        }

        $firstPage = $this->actingAs($admin, 'admin')->get(route('admin.orders.index', ['search' => 'DATE-PAGE-']));
        $secondPage = $this->actingAs($admin, 'admin')->get(route('admin.orders.index', ['search' => 'DATE-PAGE-', 'page' => 2]));

        $firstPage->assertViewHas('datePages', fn ($pages): bool => $pages->perPage() === 3 && $pages->count() === 3)
            ->assertSee('DATE-PAGE-0')
            ->assertDontSee('DATE-PAGE-3');
        $secondPage->assertSee('DATE-PAGE-3')
            ->assertDontSee('DATE-PAGE-0');
    }

    public function test_order_index_displays_cambodia_local_time(): void
    {
        $this->travelTo('2026-09-17 13:01:00');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::query()->where('role', 'customer')->firstOrFail();
        $this->createOrder($customer, 'CAMBODIA-TIME-TEST', now(), now());

        $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.index', ['search' => 'CAMBODIA-TIME-TEST']));

        $response->assertSee('08:01 PM');
    }

    public function test_order_index_can_filter_paid_orders(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = User::query()->where('role', 'customer')->firstOrFail();
        $paidOrder = $this->createOrder($customer, 'PAYMENT-FILTER-PAID', now(), now());
        $unpaidOrder = $this->createOrder($customer, 'PAYMENT-FILTER-UNPAID', now(), now());
        $paidOrder->update(['payment_status' => 'paid']);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.index', [
            'search' => 'PAYMENT-FILTER-',
            'payment_status' => 'paid',
        ]));

        $response->assertSee($paidOrder->order_number)
            ->assertDontSee($unpaidOrder->order_number);
    }

    public function test_order_overview_and_printable_label_show_delivery_information(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $order = Order::query()->whereHas('items')->firstOrFail();
        $order->update(['order_number' => 'TS-TEST-LABEL-X9K2Q']);

        $this->actingAs($admin, 'admin')->get(route('admin.orders.show', $order))
            ->assertSee($order->order_number)
            ->assertSee($order->recipient_name)
            ->assertSee($order->delivery_address_line_1)
            ->assertSee('Print delivery label');

        $this->actingAs($admin, 'admin')->get(route('admin.orders.label', $order))
            ->assertSee('Delivery label')
            ->assertSeeInOrder(['TRENDSHOP', 'DELIVERY LABEL', 'REFERENCE', '#X9K2Q'])
            ->assertSee('#X9K2Q')
            ->assertSee($order->recipient_phone)
            ->assertSee('class="label-footer"', false)
            ->assertSee($order->payment_status === 'paid' ? 'Payment: Paid' : 'Payment: Cash on delivery')
            ->assertDontSee($order->order_number)
            ->assertDontSee('barcode');
    }

    public function test_marking_order_delivered_opens_the_printable_label(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $order = Order::query()->firstOrFail();
        $order->update(['status' => 'pending', 'payment_status' => 'unpaid']);

        foreach (['confirmed', 'processing', 'shipped'] as $status) {
            $this->actingAs($admin, 'admin')->patch(route('admin.orders.update', $order), [
                'status' => $status,
                'payment_status' => 'pending',
            ])->assertRedirect();
            $order->refresh();
        }
        $response = $this->actingAs($admin, 'admin')->patch(route('admin.orders.update', $order), ['status' => 'delivered', 'payment_status' => 'paid']);

        $response->assertRedirect(route('admin.orders.label', ['order' => $order, 'updated' => 1]));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered', 'payment_status' => 'paid']);
    }

    public function test_order_cannot_skip_the_fulfillment_sequence(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $order = Order::query()->firstOrFail();
        $order->update(['status' => 'pending', 'payment_status' => 'unpaid']);

        $this->actingAs($admin, 'admin')->patch(route('admin.orders.update', $order), [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ])->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->fresh()->status);
    }

    /** Create one deterministic order for date grouping tests. */
    private function createOrder(User $customer, string $orderNumber, CarbonInterface $placedAt, CarbonInterface $createdAt): Order
    {
        return Order::query()->create([
            'order_number' => $orderNumber,
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'currency' => 'USD',
            'subtotal' => 10,
            'discount_total' => 0,
            'delivery_fee' => 2,
            'grand_total' => 12,
            'recipient_name' => 'Date Test Customer',
            'recipient_phone' => '012345678',
            'delivery_address_line_1' => 'Test street',
            'delivery_city_province' => 'Phnom Penh',
            'delivery_country_code' => 'KH',
            'placed_at' => $placedAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
