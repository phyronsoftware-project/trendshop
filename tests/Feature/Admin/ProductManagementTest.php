<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('products')) {
            $this->markTestSkipped('TrendShop MySQL schema is required.');
        }
    }

    public function test_admin_product_index_uses_ten_row_pagination(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.index'));

        $response->assertViewHas('products', fn ($products): bool => $products->perPage() === 10 && $products->count() <= 10)
            ->assertSee('of '.$response->viewData('products')->total().' products');
    }

    public function test_admin_creates_product_with_three_translations(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $category = Category::query()->firstOrFail();
        $payload = [
            'category_id' => $category->id, 'sku' => 'TEST-ADMIN-001', 'slug' => 'test-admin-product',
            'price' => 10, 'compare_at_price' => 12, 'stock_quantity' => 5, 'status' => 'active', 'is_featured' => 1,
            'translations' => [
                'km' => ['name' => 'ផលិតផលសាកល្បង', 'short_description' => 'សាកល្បង', 'description' => 'សាកល្បង'],
                'en' => ['name' => 'Admin Test Product', 'short_description' => 'Test', 'description' => 'Test'],
                'zh' => ['name' => '管理测试产品', 'short_description' => '测试', 'description' => '测试'],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')->post(route('admin.products.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $product = Product::query()->where('sku', 'TEST-ADMIN-001')->firstOrFail();
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertSame(3, $product->translations()->count());
    }

    /** Ensure deleting an uploaded image removes both its record and file. */
    public function test_admin_deletes_an_uploaded_product_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/delete-me.jpg', 'image-content');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $product = Product::query()->with('images')->firstOrFail();
        $product->images()->update(['is_primary' => false]);
        $image = $product->images()->create([
            'image_path' => 'storage/products/delete-me.jpg',
            'is_primary' => true,
            'sort_order' => 999,
        ]);

        $response = $this->actingAs($admin, 'admin')->delete(route('admin.products.images.destroy', [$product, $image]));

        $response->assertRedirect();
        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing('products/delete-me.jpg');
        $this->assertTrue($product->images()->firstOrFail()->is_primary);
    }

    /** Ensure scoped binding hides an image that belongs to another product. */
    public function test_admin_cannot_delete_an_image_through_the_wrong_product(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $products = Product::query()->with('images')->take(2)->get();
        $image = $products->last()->images->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.products.images.destroy', [$products->first(), $image]))
            ->assertNotFound();

        $this->assertModelExists($image);
    }
}
