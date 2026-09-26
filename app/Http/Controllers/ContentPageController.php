<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use Illuminate\View\View;

class ContentPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = ContentPage::query()->where(['slug' => $slug, 'status' => 'published'])->with('translations')->firstOrFail();

        return view($slug === 'about-us' ? 'pages.about' : 'pages.privacy', compact('page'));
    }
}
