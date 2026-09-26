@extends('layouts.storefront')

@section('title', 'TrendShop — About us')

@section('content')
    @php($content = $page->translation())
    {{-- Explain the storefront purpose with a compact customer-facing page. --}}
    <section class="relative overflow-hidden bg-white py-16 dark:bg-black sm:py-24">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(23,63,136,0.14),transparent_38%)]"></div>
        <div class="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-[#1f62c9] dark:text-blue-400" data-i18n="about.eyebrow">ABOUT TRENDSHOP</p>
            <h1 data-page-heading class="mt-4 max-w-4xl text-4xl font-bold leading-tight tracking-tight text-slate-950 dark:text-white sm:text-6xl">{{ $content?->title }}</h1>
            <p data-page-copy class="mt-6 max-w-3xl text-lg leading-9 text-slate-600 dark:text-slate-400">{{ $content?->summary }}</p>
            {{-- Content is sanitized before storage by the admin editor. --}}
            <div class="prose mt-6 max-w-3xl text-base leading-8 text-slate-600 dark:text-slate-400">{!! $content?->content !!}</div>

            <div class="mt-14 grid gap-6 md:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-[#f7f9fc] p-8 dark:border-slate-800 dark:bg-[#0e1113]">
                    <span class="grid size-12 place-items-center rounded-2xl bg-[#173f88] text-white">✓</span>
                    <h2 class="mt-6 text-xl font-bold text-slate-950 dark:text-white" data-i18n="about.qualityTitle">Carefully selected</h2>
                    <p class="mt-3 leading-7 text-slate-600 dark:text-slate-400" data-i18n="about.qualityText">We focus on practical products, clear information and fair value.</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-[#f7f9fc] p-8 dark:border-slate-800 dark:bg-[#0e1113]">
                    <span class="grid size-12 place-items-center rounded-2xl bg-[#173f88] text-white">♡</span>
                    <h2 class="mt-6 text-xl font-bold text-slate-950 dark:text-white" data-i18n="about.serviceTitle">Customer first</h2>
                    <p class="mt-3 leading-7 text-slate-600 dark:text-slate-400" data-i18n="about.serviceText">Our goal is a smooth experience before, during and after every order.</p>
                </article>
            </div>
        </div>
    </section>
@endsection
