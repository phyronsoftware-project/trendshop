<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $categories = Category::query()->withCount('products')->with('translations')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->persist(new Category, $request->validated(), $request);

        return back()->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    /**
     * Show the form for editing the specified resource.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'slug' => ['required', 'alpha_dash', 'max:191', Rule::unique('categories')->ignore($category)],
            'sort_order' => ['required', 'integer', 'min:0'], 'is_active' => ['nullable', 'boolean'],
            'translations' => ['required', 'array'], 'translations.*.name' => ['required', 'string', 'max:150'], 'translations.*.description' => ['nullable', 'string'],
        ]);
        $this->persist($category, $data, $request);

        return back()->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->products()->exists(), 422, 'Move products before removing this category.');
        $category->delete();

        return back()->with('success', 'Category removed.');
    }

    /** @param array<string, mixed> $data */
    private function persist(Category $category, array $data, Request $request): void
    {
        DB::transaction(function () use ($category, $data, $request): void {
            $category->fill(['slug' => $data['slug'], 'sort_order' => $data['sort_order'], 'is_active' => $request->boolean('is_active')])->save();
            foreach (['km', 'en', 'zh'] as $locale) {
                $category->translations()->updateOrCreate(['locale' => $locale], $data['translations'][$locale]);
            }
        });
    }
}
