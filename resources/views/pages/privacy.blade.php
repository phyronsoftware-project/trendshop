@extends('layouts.storefront')

@section('title', 'TrendShop — Privacy Policy')

@section('content')
    @php($content = $page->translation())
    {{-- Present a readable placeholder policy for the frontend design phase. --}}
    <section class="bg-white py-16 dark:bg-black sm:py-24">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-[#1f62c9] dark:text-blue-400" data-i18n="privacy.eyebrow">YOUR PRIVACY</p>
            <h1 data-page-heading class="mt-4 text-4xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-6xl">{{ $content?->title }}</h1>
            <p class="mt-4 text-sm font-semibold text-slate-400" data-i18n="privacy.updated">Last updated: September 2026</p>

            <div class="mt-12 flex flex-col gap-5">
                {{-- Content is sanitized before storage by the admin editor. --}}
                <article class="rounded-3xl border border-slate-200 bg-[#f7f9fc] p-7 leading-8 text-slate-600 dark:border-slate-800 dark:bg-[#0e1113] dark:text-slate-400 sm:p-9">{!! $content?->content !!}</article>
                @foreach ([
                    ['introTitle', 'introText', '01'],
                    ['useTitle', 'useText', '02'],
                    ['protectTitle', 'protectText', '03'],
                ] as [$titleKey, $textKey, $number])
                    <article class="grid gap-5 rounded-3xl border border-slate-200 bg-[#f7f9fc] p-7 dark:border-slate-800 dark:bg-[#0e1113] sm:grid-cols-[auto_1fr] sm:p-9">
                        <span class="grid size-12 place-items-center rounded-2xl bg-[#173f88] text-sm font-bold text-white">{{ $number }}</span>
                        <div>
                            <h2 class="text-xl font-bold text-slate-950 dark:text-white" data-i18n="privacy.{{ $titleKey }}">{{ $titleKey }}</h2>
                            <p class="mt-3 leading-8 text-slate-600 dark:text-slate-400" data-i18n="privacy.{{ $textKey }}">{{ $textKey }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
