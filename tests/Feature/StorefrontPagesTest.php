<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontPagesTest extends TestCase
{
    /** Ensure each static customer page remains available. */
    public function test_customer_storefront_pages_are_available(): void
    {
        $pages = ['/', '/login', '/about-us', '/privacy-policy'];

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }
    }

    /** Ensure the product page includes its filtering interface. */
    public function test_product_page_displays_the_static_catalogue(): void
    {
        $this->get('/')
            ->assertSee('Studio Wireless Headphones')
            ->assertSee('category-filter')
            ->assertSee('product-search')
            ->assertSee('data-pagination', false)
            ->assertSee('flag/us.png', false)
            ->assertSee('flag/china.png', false)
            ->assertSee('logo_web/image.png', false)
            ->assertSee('Trusted shopping, made simple')
            ->assertSee('data-store-benefits', false)
            ->assertSee('data-top-seller-marquee', false)
            ->assertDontSee('data-best-seller-feature', false)
            ->assertViewHas('topSellers', fn ($topSellers): bool => $topSellers->count() <= 10
                && $topSellers->pluck('sold_quantity')->values()->all() === $topSellers->pluck('sold_quantity')->sortDesc()->values()->all())
            ->assertViewHas('storeSummary', fn (array $summary): bool => isset($summary['customers_count'], $summary['delivered_orders_count'], $summary['items_sold'], $summary['products_count']));
    }

    /** Ensure customer login remains independent from the storefront chrome. */
    public function test_login_page_does_not_display_the_header_or_footer(): void
    {
        $this->get('/login')
            ->assertSee('data-i18n="login.email"', false)
            ->assertDontSee('សូមស្វាគមន៍មកវិញ')
            ->assertSee('Logo-Socail/google.png', false)
            ->assertSee('bg-[#173f88]', false)
            ->assertDontSee('<header', false)
            ->assertDontSee('<footer', false)
            ->assertDontSee('bg-linear', false);
    }

    /** Ensure the signed-in header provides account actions beside the customer email. */
    public function test_authenticated_header_displays_the_account_dropdown(): void
    {
        if (! Schema::hasTable('users')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        $customer = User::query()->where('role', 'customer')->firstOrFail();

        $this->actingAs($customer)
            ->get('/')
            ->assertOk()
            ->assertSee($customer->email)
            ->assertSee('Trusted shopping, made simple')
            ->assertSee('data-account-menu', false)
            ->assertSee('My profile')
            ->assertSee('Logout');
    }

    /** Ensure product recommendations stay inside the selected category. */
    public function test_product_detail_displays_related_products_from_the_same_category(): void
    {
        if (! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }

        $product = Product::query()->active()->with('translations')->firstOrFail();
        $related = Product::query()->active()->where('category_id', $product->category_id)->whereKeyNot($product->id)->with('translations')->firstOrFail();
        $unrelated = Product::query()->active()->where('category_id', '!=', $product->category_id)->with('translations')->firstOrFail();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee($related->translation('en')?->name)
            ->assertDontSee($unrelated->translation('en')?->name);
    }
}
