<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }
    }

    public function test_customer_without_address_cannot_buy_product(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::query()->active()->firstOrFail();

        $this->actingAs($customer)->get(route('products.show', $product))
            ->assertSee('Add a delivery address before buying this product.')
            ->assertSee('disabled', false)
            ->assertSee('Add delivery address');
    }

    public function test_customer_can_verify_complete_address_before_buying(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->addresses()->create([
            'label' => 'Office',
            'recipient_name' => 'Checkout Customer',
            'phone' => '012345678',
            'address_line_1' => 'Street 2004',
            'commune' => 'Tuek Thla',
            'district' => 'Sen Sok',
            'city_province' => 'Phnom Penh',
            'country_code' => 'KH',
            'is_default' => true,
        ]);
        $product = Product::query()->active()->firstOrFail();

        $this->actingAs($customer)->get(route('products.show', $product))
            ->assertSee('Checkout Customer')
            ->assertSee('012345678')
            ->assertSee('Street 2004, Tuek Thla, Sen Sok, Phnom Penh')
            ->assertSee('$1.00')
            ->assertSee('data-delivery-address-preview', false);
    }

    /** Keep successful direct checkout on the selected product page. */
    public function test_successful_product_purchase_redirects_to_product_detail(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $address = $customer->addresses()->create([
            'label' => 'Home',
            'recipient_name' => 'Checkout Customer',
            'phone' => '012345678',
            'address_line_1' => 'Street 2004',
            'commune' => 'Tuek Thla',
            'district' => 'Sen Sok',
            'city_province' => 'Phnom Penh',
            'country_code' => 'KH',
            'is_default' => true,
        ]);
        $product = Product::query()->active()->where('stock_quantity', '>', 0)->firstOrFail();
        $csrfToken = 'product-checkout-token';

        $response = $this->actingAs($customer)->withSession(['_token' => $csrfToken])->post(route('orders.store'), [
            '_token' => $csrfToken,
            'address_id' => $address->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'source' => 'product',
        ]);

        $response
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('orders', ['user_id' => $customer->id, 'user_address_id' => $address->id]);
    }
}
