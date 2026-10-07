<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartToggleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ajax_request_adds_and_removes_a_cart_item_without_redirecting(): void
    {
        if (! Schema::hasTable('carts') || ! Schema::hasTable('cart_items') || ! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        // Arrange one active product for the authenticated customer's toggle request.
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $product = Product::query()->active()->orderByDesc('published_at')->orderByDesc('id')->firstOrFail();

        $this->actingAs($customer)
            ->postJson(route('cart.store', $product), ['quantity' => 1])
            ->assertOk()
            ->assertJson(['in_cart' => true, 'message' => 'Product added to cart.']);
        $cart = Cart::query()->where(['user_id' => $customer->id, 'status' => 'active'])->firstOrFail();
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($customer)
            ->postJson(route('cart.store', $product), ['quantity' => 1])
            ->assertOk()
            ->assertJson(['in_cart' => false, 'message' => 'Product removed from cart.']);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id, 'product_id' => $product->id]);
    }

    public function test_home_page_renders_the_filled_cart_state_for_a_customer(): void
    {
        if (! Schema::hasTable('carts') || ! Schema::hasTable('cart_items') || ! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        // Persist one cart item so the matching product card renders as selected.
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $product = Product::query()->active()->orderByDesc('published_at')->orderByDesc('id')->firstOrFail();
        $cart = Cart::query()->create(['user_id' => $customer->id, 'status' => 'active', 'currency' => 'USD']);
        CartItem::query()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => $product->price]);

        $this->actingAs($customer)
            ->get(route('products.index'))
            ->assertSee('data-cart-ajax', false)
            ->assertSee('actions.removeCart', false)
            ->assertSee('aria-pressed="true"', false);
    }

    public function test_regular_cart_form_keeps_its_existing_quantity_increment_behavior(): void
    {
        if (! Schema::hasTable('carts') || ! Schema::hasTable('cart_items') || ! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        // Submit regular browser forms twice to protect the existing quantity flow.
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $product = Product::query()->active()->where('stock_quantity', '>=', 2)->firstOrFail();

        $this->actingAs($customer)->from(route('products.show', $product))->post(route('cart.store', $product), ['quantity' => 1])->assertRedirect();
        $this->actingAs($customer)->from(route('products.show', $product))->post(route('cart.store', $product), ['quantity' => 1])->assertRedirect();

        $cart = Cart::query()->where(['user_id' => $customer->id, 'status' => 'active'])->firstOrFail();
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);
    }
}
