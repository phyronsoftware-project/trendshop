<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContentPageRequest;
use App\Models\ContentPage;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContentPageController extends Controller
{
    public function __construct(private readonly HtmlSanitizer $htmlSanitizer) {}

    public function index(): View
    {
        return view('admin.content.index', ['pages' => ContentPage::query()->with('translations')->orderBy('id')->get()]);
    }

    public function edit(ContentPage $page): View
    {
        $page->load('translations');

        return view('admin.content.form', compact('page'));
    }

    public function update(UpdateContentPageRequest $request, ContentPage $page): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $page, $data): void {
            $page->update(['status' => $data['status'], 'updated_by' => $request->user()->id, 'published_at' => $data['status'] === 'published' ? ($page->published_at ?? now()) : $page->published_at]);
            foreach (['km', 'en', 'zh'] as $locale) {
                $translation = $data['translations'][$locale];
                $translation['content'] = $this->htmlSanitizer->sanitize($translation['content'] ?? null);
                $page->translations()->updateOrCreate(['locale' => $locale], $translation);
            }
        });

        return back()->with('success', 'Page content updated.');
    }
}
