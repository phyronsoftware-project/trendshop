<?php

namespace Tests\Unit\Models;

use App\Models\ProductImage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    /** Ensure uploaded image paths are absolute from the application root. */
    public function test_uploaded_image_path_returns_a_root_safe_url(): void
    {
        $image = new ProductImage(['image_path' => 'storage/products/example.jpg']);

        $this->assertSame(asset('storage/products/example.jpg'), $image->url());
    }
}
