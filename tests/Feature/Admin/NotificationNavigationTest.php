<?php

namespace Tests\Feature\Admin;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationNavigationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('admin_notifications')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }
    }

    public function test_opening_order_notification_marks_it_read_and_opens_the_order(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $order = Order::query()->firstOrFail();
        $notification = AdminNotification::query()->create([
            'admin_user_id' => $admin->id,
            'order_id' => $order->id,
            'type' => 'new_order',
            'title' => 'New test order',
            'message' => 'A test order was placed.',
            'data' => [],
            'read_at' => null,
        ]);
        $expectedUrl = route('admin.orders.show', $order);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.notifications.open', $notification));

        $response->assertRedirect($expectedUrl);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notifications_are_grouped_under_today_and_yesterday_labels(): void
    {
        $this->freezeTime();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $order = Order::query()->firstOrFail();
        foreach ([['Today test notification', now()], ['Yesterday test notification', now()->subDay()]] as [$title, $createdAt]) {
            AdminNotification::query()->create([
                'admin_user_id' => $admin->id,
                'order_id' => $order->id,
                'type' => 'new_order',
                'title' => $title,
                'message' => 'Notification grouping test.',
                'data' => [],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $response = $this->actingAs($admin, 'admin')->get(route('admin.notifications.index'));

        $response->assertSee('Today test notification')
            ->assertSee('Yesterday test notification')
            ->assertSee('Today')
            ->assertSee('Yesterday');
    }

    public function test_notifications_paginate_three_complete_date_groups_per_page(): void
    {
        $this->freezeTime();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $order = Order::query()->firstOrFail();

        foreach (range(0, 3) as $daysAgo) {
            AdminNotification::query()->create([
                'admin_user_id' => $admin->id,
                'order_id' => $order->id,
                'type' => 'new_order',
                'title' => "Pagination notification {$daysAgo}",
                'message' => 'Notification pagination test.',
                'data' => [],
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ]);
        }

        $firstPage = $this->actingAs($admin, 'admin')->get(route('admin.notifications.index'));
        $secondPage = $this->actingAs($admin, 'admin')->get(route('admin.notifications.index', ['page' => 2]));

        $firstPage->assertViewHas('datePages', fn ($pages): bool => $pages->perPage() === 3 && $pages->count() === 3)
            ->assertSee('Pagination notification 0')
            ->assertDontSee('Pagination notification 3');
        $secondPage->assertSee('Pagination notification 3')
            ->assertDontSee('Pagination notification 0');
    }

    public function test_notifications_display_cambodia_local_time(): void
    {
        $this->travelTo('2026-09-17 13:01:00');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $order = Order::query()->firstOrFail();
        AdminNotification::query()->create([
            'admin_user_id' => $admin->id,
            'order_id' => $order->id,
            'type' => 'new_order',
            'title' => 'Cambodia time notification',
            'message' => 'Timezone display test.',
            'data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.notifications.index'));

        $response->assertSee('Cambodia time notification')
            ->assertSee('08:01 PM');
    }

    public function test_notification_pagination_controls_remain_visible_on_one_page(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $order = Order::query()->firstOrFail();
        AdminNotification::query()->delete();
        AdminNotification::query()->create([
            'admin_user_id' => $admin->id,
            'order_id' => $order->id,
            'type' => 'new_order',
            'title' => 'Only notification page',
            'message' => 'Single page pagination test.',
            'data' => [],
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.notifications.index'));

        $response->assertSee('aria-label="Pagination"', false)
            ->assertSee('aria-current="page">1</span>', false);
    }
}
