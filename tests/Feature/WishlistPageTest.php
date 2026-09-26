<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WishlistPageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_wishlist_matches_the_five_column_grid_and_renders_filled_ajax_favorites(): void
    {
        if (! Schema::hasTable('wishlist_items') || ! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $product = Product::query()->firstOrFail();
        WishlistItem::query()->create(['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->actingAs($customer)
            ->get(route('wishlist.index'))
            ->assertSee('xl:grid-cols-5', false)
            ->assertSee('data-wishlist-remove-card', false)
            ->assertSee('aria-pressed="true"', false)
            ->assertDontSee('data-wishlist-double-click', false);
    }

    public function test_ajax_request_adds_and_removes_a_wishlist_item_without_redirecting(): void
    {
        if (! Schema::hasTable('wishlist_items') || ! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $product = Product::query()->firstOrFail();

        $this->actingAs($customer)
            ->postJson(route('wishlist.toggle', $product))
            ->assertOk()
            ->assertJson(['wishlisted' => true, 'message' => 'Product saved to wishlist.']);
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->actingAs($customer)
            ->postJson(route('wishlist.toggle', $product))
            ->assertOk()
            ->assertJson(['wishlisted' => false, 'message' => 'Product removed from wishlist.']);
        $this->assertDatabaseMissing('wishlist_items', ['user_id' => $customer->id, 'product_id' => $product->id]);
    }

    public function test_home_page_renders_the_saved_favorite_state_for_a_customer(): void
    {
        if (! Schema::hasTable('wishlist_items') || ! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $product = Product::query()->active()->firstOrFail();
        WishlistItem::query()->create(['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->actingAs($customer)
            ->get(route('products.index'))
            ->assertSee('data-wishlist-ajax', false)
            ->assertSee('aria-pressed="true"', false);
    }
}
