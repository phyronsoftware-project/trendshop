<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddressManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('user_addresses')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }
    }

    /** Ensure customers can update their own saved address. */
    public function test_customer_can_update_their_address(): void
    {
        $customer = User::query()->where('role', 'customer')->firstOrFail();
        $address = UserAddress::query()->create([
            'user_id' => $customer->id,
            'label' => 'Test address',
            'recipient_name' => 'Original recipient',
            'phone' => '012345678',
            'address_line_1' => 'Original street',
            'city_province' => 'Phnom Penh',
            'country_code' => 'KH',
            'is_default' => false,
        ]);

        $response = $this->actingAs($customer)->patch(route('addresses.update', $address), [
            'label' => 'Office',
            'recipient_name' => 'Updated recipient',
            'phone' => '098765432',
            'address_line_1' => 'Updated street',
            'commune' => 'Boeung Keng Kang 1',
            'district' => 'Boeung Keng Kang',
            'city_province' => 'Phnom Penh',
            'postal_code' => '120102',
        ]);

        $response->assertRedirect(route('profile').'#addresses');
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'user_id' => $customer->id,
            'label' => 'Office',
            'recipient_name' => 'Updated recipient',
            'address_line_1' => 'Updated street',
        ]);
    }

    /** Ensure one customer cannot update another account's address. */
    public function test_customer_cannot_update_another_users_address(): void
    {
        $customer = User::query()->where('role', 'customer')->firstOrFail();
        $otherUser = User::query()->whereKeyNot($customer->id)->firstOrFail();
        $address = UserAddress::query()->create([
            'user_id' => $otherUser->id,
            'label' => 'Private address',
            'recipient_name' => 'Other customer',
            'phone' => '011111111',
            'address_line_1' => 'Private street',
            'city_province' => 'Phnom Penh',
            'country_code' => 'KH',
            'is_default' => false,
        ]);

        $this->actingAs($customer)->patch(route('addresses.update', $address), [
            'label' => 'Changed',
            'recipient_name' => 'Changed recipient',
            'phone' => '099999999',
            'address_line_1' => 'Changed street',
            'city_province' => 'Siem Reap',
        ])->assertNotFound();

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'label' => 'Private address',
            'address_line_1' => 'Private street',
        ]);
    }

    /** Ensure delivery addresses use an official Cambodian province. */
    public function test_customer_cannot_save_an_unknown_province(): void
    {
        $customer = User::query()->where('role', 'customer')->firstOrFail();

        $response = $this->actingAs($customer)->post(route('addresses.store'), [
            'label' => 'Invalid province',
            'recipient_name' => 'TrendShop Customer',
            'phone' => '012345678',
            'address_line_1' => 'Street 1',
            'city_province' => 'Unknown Province',
        ]);

        $response->assertSessionHasErrors('city_province');
        $this->assertDatabaseMissing('user_addresses', [
            'user_id' => $customer->id,
            'label' => 'Invalid province',
        ]);
    }
}
