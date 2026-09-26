@extends('layouts.storefront')
@section('title', 'TrendShop — Wishlist')
@section('content')
{{-- Match the catalogue with two cards on phones and five cards on wide screens. --}}
<section class="min-h-[65vh] bg-[#f7f9fc] py-12 dark:bg-black"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><h1 class="text-3xl font-bold">My wishlist</h1><div data-wishlist-grid class="mt-6 grid grid-cols-2 items-stretch gap-2 sm:gap-3 lg:grid-cols-3 xl:grid-cols-5 xl:gap-3.5">@foreach($items as $item)<x-product-card :product="$item->product" :index="$loop->index" :wishlist-mode="true" :wishlisted="true" :in-cart="$cartProductIds->contains($item->product_id)" />@endforeach<p data-wishlist-empty @class(['col-span-full border border-slate-200 bg-white p-8 text-center text-slate-500 dark:border-slate-800 dark:bg-[#0e1113]', 'hidden' => $items->isNotEmpty()])>Your wishlist is empty.</p></div></div></section>
@endsection
