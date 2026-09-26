<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly HtmlSanitizer $htmlSanitizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $products = Product::query()->with(['translations', 'images', 'category.translations'])
            ->when($request->string('search')->isNotEmpty(), fn ($query) => $query->where(fn ($inner) => $inner->where('sku', 'like', '%'.$request->string('search').'%')->orWhereHas('translations', fn ($translation) => $translation->where('name', 'like', '%'.$request->string('search').'%'))))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            // Keep the dashboard catalogue to ten manageable rows per page.
            ->latest()->paginate(10)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product, 'categories' => Category::query()->with('translations')->orderBy('sort_order')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->persist(new Product, $request->validated(), $request);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        $product->load(['translations', 'images']);

        return view('admin.products.form', ['product' => $product, 'categories' => Category::query()->with('translations')->orderBy('sort_order')->get()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->persist($product, $request->validated(), $request);

        return back()->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product removed.');
    }

    /** Remove one uploaded image and keep a valid primary image when possible. */
    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $publicDiskPath = $image->publicDiskPath();
        $wasPrimary = $image->is_primary;

        DB::transaction(function () use ($product, $image, $wasPrimary): void {
            $image->delete();

            if ($wasPrimary) {
                $product->images()->first()?->update(['is_primary' => true]);
            }
        });

        if ($publicDiskPath !== null) {
            Storage::disk('public')->delete($publicDiskPath);
        }

        return back()->with('success', 'Product image removed successfully.');
    }

    /** @param array<string, mixed> $data */
    private function persist(Product $product, array $data, Request $request): Product
    {
        return DB::transaction(function () use ($product, $data, $request): Product {
            $product->fill([
                'category_id' => $data['category_id'], 'sku' => $data['sku'], 'slug' => $data['slug'],
                'price' => $data['price'], 'compare_at_price' => $data['compare_at_price'] ?? null,
                'stock_quantity' => $data['stock_quantity'], 'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'), 'published_at' => $data['status'] === 'active' ? ($product->published_at ?? now()) : $product->published_at,
            ])->save();

            foreach (['km', 'en', 'zh'] as $locale) {
                $translation = $data['translations'][$locale];
                $translation['description'] = $this->htmlSanitizer->sanitize($translation['description'] ?? null);
                $product->translations()->updateOrCreate(['locale' => $locale], $translation);
            }

            foreach ($request->file('images', []) as $index => $image) {
                $product->images()->create(['image_path' => 'storage/'.$image->store('products', 'public'), 'is_primary' => ! $product->images()->exists() && $index === 0, 'sort_order' => $product->images()->max('sort_order') + 1]);
            }

            return $product;
        });
    }
}
