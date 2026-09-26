<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="TrendShop administrator sign in">

        <title>TrendShop — Administrator sign in</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#173f88] text-white antialiased">
        <main class="flex min-h-screen items-center justify-center px-5 py-12 sm:px-8">
            <section class="w-full max-w-sm">
                <div class="text-center">
                    <img src="{{ asset('logo_web/image.png') }}" alt="TrendShop logo" class="mx-auto size-16 object-contain drop-shadow-xl">
                    <p class="mt-5 text-xs font-bold uppercase tracking-[0.22em] text-blue-200">Restricted access</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Administrator sign in</h1>
                    <p class="mt-2 text-xs leading-6 text-white/65">Use an active TrendShop administrator account.</p>
                </div>

                {{-- Authenticate administrators through their independent dashboard guard. --}}
                <form method="POST" action="{{ route('admin.login.store') }}" class="mt-7 flex flex-col gap-4">
                    @csrf
                    @if ($errors->any())
                        <p class="rounded-lg border border-red-200 bg-red-950/20 px-3 py-2 text-xs text-red-100">{{ $errors->first() }}</p>
                    @endif
                    @if (session('success'))
                        <p class="rounded-lg border border-emerald-200 bg-emerald-950/20 px-3 py-2 text-xs text-emerald-100">{{ session('success') }}</p>
                    @endif
                    <label class="flex flex-col gap-2 text-xs font-semibold">
                        <span>Email address</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="admin@example.com" class="h-11 rounded-lg border border-white/35 bg-transparent px-3.5 text-sm font-normal outline-none transition-all duration-300 placeholder:text-white/35 focus:border-white focus:ring-2 focus:ring-white/15">
                    </label>
                    <label class="flex flex-col gap-2 text-xs font-semibold">
                        <span>Password</span>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="Enter administrator password" class="h-11 rounded-lg border border-white/35 bg-transparent px-3.5 text-sm font-normal outline-none transition-all duration-300 placeholder:text-white/35 focus:border-white focus:ring-2 focus:ring-white/15">
                    </label>
                    <label class="flex items-center gap-2 text-xs text-white/80">
                        <input type="checkbox" name="remember" value="1" class="size-3.5 rounded border-white/40 bg-transparent text-white">
                        <span>Remember me</span>
                    </label>
                    <button type="submit" class="h-11 rounded-lg bg-white text-sm font-bold text-[#173f88] shadow-lg shadow-blue-950/15 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl active:scale-[.98]">Sign in to dashboard</button>
                </form>

                <a href="{{ route('products.index') }}" class="mt-5 block text-center text-xs font-semibold text-white/70 transition-colors hover:text-white">← Return to storefront</a>
            </section>
        </main>
    </body>
</html>
