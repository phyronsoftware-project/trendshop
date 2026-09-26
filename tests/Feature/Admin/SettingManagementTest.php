<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettingManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('settings')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }
    }

    /** Ensure an administrator can persist all province delivery fees. */
    public function test_admin_updates_province_delivery_fees(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $response = $this->actingAs($admin, 'admin')->put(route('admin.settings.update'), [
            'settings' => [
                'city_delivery_fee' => 1.25,
                'province_delivery_fee' => 2,
                'free_delivery_minimum' => 100,
                'support_email' => 'support@trendshop.test',
                'support_phone' => '+855 12 345 678',
            ],
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();
        $storedFees = json_decode(Setting::query()->where('setting_key', 'province_delivery_fees')->value('value'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1.25, $storedFees['Phnom Penh']);
        $this->assertSame(2.0, (float) $storedFees['Siem Reap']);
        $this->assertSame(2.0, (float) $storedFees['Battambang']);
    }
}
